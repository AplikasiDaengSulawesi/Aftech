import 'dart:async';
import 'dart:ui';
import 'dart:io';
import 'package:aftech/services/bluetooth_service.dart';
import 'package:aftech/services/database_service.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_pos_printer_platform_image_3/flutter_pos_printer_platform_image_3.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';
import 'package:device_info_plus/device_info_plus.dart';
import 'package:curved_navigation_bar/curved_navigation_bar.dart';

import '../../models/label_data.dart';
import '../../widgets/dialogs.dart';
import '../../widgets/mini_widgets.dart';
import 'tabs/input_tab.dart';
import 'tabs/queue_tab.dart';
import 'tabs/report_tab.dart';
import 'tabs/log_tab.dart';
import 'tabs/template_tab.dart';

class PrinterScreen extends StatefulWidget {
  const PrinterScreen({super.key});
  @override
  State<PrinterScreen> createState() => _PrinterScreenState();
}

class _PrinterScreenState extends State<PrinterScreen> with TickerProviderStateMixin {
  final BluetoothService _bluetoothService = BluetoothService();
  final DatabaseService _dbService = DatabaseService.instance;
  final DeviceInfoPlugin _deviceInfo = DeviceInfoPlugin();
  
  // --- STATE ---
  List<PrinterDevice> _devices = []; PrinterDevice? _selectedDevice;
  bool _connected = false; bool _isIpConnected = false; 
  bool _isSyncing = false; bool _isProcessing = false; 
  bool _shouldStopPrinting = false; bool _skipPrintConfirmation = false;
  bool _isTestingConn = false;
  
  late Timer _timer; String _currentTime = ""; DateTime _selectedDate = DateTime.now();
  int _currentTabIndex = 2; // Default: INPUT
  int _currentQueueSubTab = 0;
  List<LabelData> _printQueue = []; List<int> _selectedIds = [];
  String _searchQuery = ""; List<dynamic> _reports = []; List<Map<String, dynamic>> _localLogs = [];
  List<dynamic> _templates = []; bool _isLoadingReports = false; bool _isLoadingTemplates = false;
  String? _deviceModel;

  final _sizeValueController = TextEditingController(text: "600");
  final _qtyController = TextEditingController(text: "1200");
  final _labelCountController = TextEditingController(text: "1");
  final _opController = TextEditingController();
  final _qcController = TextEditingController();
  final _batchController = TextEditingController();
  final _ipPrinterController = TextEditingController(text: "192.168.1.100");

  String _selectedItem = ""; String _selectedUnit = ""; 
  String _selectedMachine = ""; String _selectedShift = "";
  List<String> _items = []; List<String> _units = []; 
  List<String> _machines = []; List<String> _shifts = [];
  List<String> _availableSizes = []; List<String> _availableQuantities = [];
  List<Map<String, dynamic>> _machineDetails = []; List<Map<String, dynamic>> _itemDetails = [];

  late AnimationController _pulseController;

  @override
  void initState() {
    super.initState(); _updateTime();
    _timer = Timer.periodic(const Duration(seconds: 1), (t) => _updateTime());
    _pulseController = AnimationController(vsync: this, duration: const Duration(seconds: 1))..repeat(reverse: true);
    _initAppData(); if (!kIsWeb) _scanDevices();
    _getDeviceModel();
  }

  Future<void> _getDeviceModel() async {
    try {
      String model = "Unknown Device";
      if (kIsWeb) {
        model = "Web Browser";
      } else if (Platform.isAndroid) {
        AndroidDeviceInfo androidInfo = await _deviceInfo.androidInfo;
        model = androidInfo.model;
      } else if (Platform.isIOS) {
        IosDeviceInfo iosInfo = await _deviceInfo.iosInfo;
        model = iosInfo.utsname.machine;
      } else if (Platform.isWindows) {
        model = "Windows PC";
      } else if (Platform.isLinux) {
        model = "Linux PC";
      } else if (Platform.isMacOS) {
        model = "MacOS Device";
      }
      if (mounted) setState(() => _deviceModel = model);
    } catch (e) { 
      debugPrint("Device Info Error: $e"); 
      if (mounted) setState(() => _deviceModel = "Device ${Platform.localHostname}");
    }
  }

  Future<void> _initAppData() async {
    await _loadMasterData(); await _loadLocalQueue(); await _loadLocalLogs(); await _loadReports(); await _loadTemplates(); _generateBatch();
  }

  @override
  void dispose() { _timer.cancel(); _pulseController.dispose(); super.dispose(); }
  void _updateTime() { if(mounted) setState(() => _currentTime = DateFormat('HH:mm:ss').format(DateTime.now())); }

  void _generateBatch() {
    if (_selectedItem.isEmpty || _selectedMachine.isEmpty || _selectedShift.isEmpty) return;
    String d = DateFormat('ddMMyy').format(_selectedDate);
    String mc = _selectedMachine.contains(" ") ? _selectedMachine.split(" ").last : _selectedMachine;
    String sh = _selectedShift.contains(" ") ? _selectedShift.substring(_selectedShift.length - 1) : _selectedShift;
    String it = _selectedItem.length > 3 ? _selectedItem.substring(0, 3) : _selectedItem;
    String op = _opController.text.length >= 2 ? _opController.text.substring(0, 2) : "OP";
    String qc = _qcController.text.length >= 2 ? _qcController.text.substring(0, 2) : "QC";
    setState(() => _batchController.text = "$d-$mc$sh-$it-${_qtyController.text}-${(op+qc).toUpperCase()}-${_sizeValueController.text}$_selectedUnit".toUpperCase());
  }

  // --- DATA LOADING ---
  Future<void> _loadLocalQueue() async { final data = await _dbService.getLocalQueue(); setState(() => _printQueue = data.map((e) => LabelData.fromMap(e)).toList()); }
  Future<void> _loadLocalLogs() async { _localLogs = await _dbService.getLocalLogs(); setState(() {}); }
  Future<void> _loadTemplates() async { 
    setState(() => _isLoadingTemplates = true); 
    _templates = await _dbService.getLocalTemplates(); 
    setState(() => _isLoadingTemplates = false); 
  }
  
  Future<void> _loadReports() async { 
    setState(() => _isLoadingReports = true); 
    try { 
      final remoteData = await _dbService.fetchRemoteReports(); setState(() { _reports = remoteData; });
      final db = await _dbService.database; bool neededHealing = false;
      await db.transaction((txn) async {
        for (var r in remoteData) {
          if (r == null || r['batch'] == null) continue;
          String b = r['batch'].toString(); int remoteCopies = int.tryParse(r['copies']?.toString() ?? "0") ?? 0;
          if (remoteCopies == 0) continue;
          var local = await txn.query('queue', where: 'batch = ? AND is_printed = 1', whereArgs: [b]);
          if (local.isEmpty) {
            neededHealing = true;
            await txn.insert('queue', {
              'item': r['item']?.toString() ?? '', 
              'size': r['size']?.toString() ?? '', 
              'unit': r['unit']?.toString() ?? '', 
              'batch': b, 
              'machine': r['machine']?.toString() ?? '', 
              'shift': r['shift']?.toString() ?? '', // TAMBAHKAN INI
              'quantity': r['quantity']?.toString() ?? '', 
              'operator': r['operator']?.toString() ?? '', 
              'qc': r['qc']?.toString() ?? '', // TAMBAHKAN INI
              'production_date': r['production_date']?.toString() ?? '', 
              'production_time': r['production_time']?.toString() ?? '', 
              'copies': remoteCopies, 
              'is_printed': 1, 
              'is_synced': 1, 
              'device_model': r['device_model']
            });
          } else {
            int localCopies = local.first['copies'] as int;
            String localShift = local.first['shift']?.toString() ?? '';
            String remoteShift = r['shift']?.toString() ?? '';
            
            // Healing jika jumlah beda ATAU data shift/qc masih kosong di lokal
            if (remoteCopies > localCopies || localShift.isEmpty) { 
              neededHealing = true; 
              await txn.update('queue', {
                'copies': remoteCopies, 
                'shift': remoteShift,
                'qc': r['qc']?.toString() ?? '',
                'device_model': r['device_model']
              }, where: 'id = ?', whereArgs: [local.first['id']]); 
            }
          }
        }
      });
      if (neededHealing) await _loadLocalQueue();
    } catch(e) { debugPrint("Healing Error: $e"); }
    setState(() => _isLoadingReports = false); 
  }

  Future<void> _loadMasterData() async {
    final items = await _dbService.getItemsWithUnits();
    final machines = await _dbService.getMachinesWithStatus();
    final shifts = await _dbService.getMasterData('shifts');
    final units = await _dbService.getMasterData('units');
    setState(() {
      // CLEAR DULU AGAR FRESH
      _items.clear(); _machines.clear(); _shifts.clear(); _units.clear();
      _itemDetails.clear(); _machineDetails.clear();

      _itemDetails = items; 
      _items = items.map((e) => e['name'] as String).toSet().toList();
      _machineDetails = machines; 
      _machines = machines.map((e) => e['name'] as String).toSet().toList();
      _shifts = (shifts.isNotEmpty ? shifts : ["SHIFT A", "SHIFT B"]).toSet().toList();
      _units = (units.isNotEmpty ? units : ["ML", "GR", "PCS"]).toSet().toList();
      if (_items.isNotEmpty) _selectedItem = _items.contains(_selectedItem) ? _selectedItem : _items.first;
      if (_machines.isNotEmpty) _selectedMachine = _machines.contains(_selectedMachine) ? _selectedMachine : _machines.first;
      if (_shifts.isNotEmpty) _selectedShift = _shifts.contains(_selectedShift) ? _selectedShift : _shifts.first;
      _updateAutoUnit(); _updateRelatedData();
    });
  }

  void _updateAutoUnit() { 
    final item = _itemDetails.firstWhere((e) => e['name'] == _selectedItem, orElse: () => {}); 
    if (item.isNotEmpty && item['unit'] != null) setState(() => _selectedUnit = item['unit']); 
  }
  Future<void> _updateRelatedData() async {
    final sizes = await _dbService.getRelatedSizes(_selectedItem); final qtys = await _dbService.getRelatedQuantities(_selectedMachine);
    setState(() { 
      _availableSizes = sizes.toSet().toList(); 
      _availableQuantities = qtys.toSet().toList(); 
      if (sizes.isNotEmpty && !_availableSizes.contains(_sizeValueController.text)) _sizeValueController.text = sizes.first; 
      if (qtys.isNotEmpty && !_availableQuantities.contains(_qtyController.text)) _qtyController.text = qtys.first; 
      _generateBatch(); 
    });
  }

  void _applyTemplate(Map<String, dynamic> t) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(25)),
        title: Column(children: [
          const Icon(Icons.flash_on_rounded, color: Colors.indigo, size: 40),
          const SizedBox(height: 10),
          Text(t['template_name'], style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900)),
          Text("${t['item']} - ${t['machine']}", style: const TextStyle(fontSize: 10, color: Colors.grey)),
        ]),
        content: Column(mainAxisSize: MainAxisSize.min, children: [
          TextField(controller: _opController, decoration: const InputDecoration(labelText: "NAMA OPERATOR", prefixIcon: Icon(Icons.person))),
          const SizedBox(height: 15),
          TextField(controller: _qcController, decoration: const InputDecoration(labelText: "NAMA QC", prefixIcon: Icon(Icons.verified_user))),
        ]),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text("BATAL")),
          ElevatedButton(
            onPressed: () async {
              Navigator.pop(ctx);
              setState(() {
                _selectedItem = t['item']; _selectedMachine = t['machine'];
                _selectedShift = t['shift']; _selectedUnit = t['unit'];
                _sizeValueController.text = t['size'].toString();
                _qtyController.text = t['quantity'].toString();
                _generateBatch();
              });
              await _addToQueue();
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.indigo[900], foregroundColor: Colors.white, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
            child: const Text("SIMPAN KE ANTREAN"),
          )
        ],
      )
    );
  }

  Future<void> _refreshDatabase() async {
    if (_isSyncing) return; 
    setState(() => _isSyncing = true);
    try {
      if (await _dbService.syncMasterData()) { 
        await _loadMasterData(); 
        await _loadReports(); 
        await _loadTemplates(); 
        _showOverlayIcon(Icons.sync, Colors.greenAccent); 
      }
    } catch (e) {
      debugPrint("Sync Error: $e");
    } finally {
      if (mounted) setState(() => _isSyncing = false);
    }
  }

  // --- ACTIONS ---
  Future<void> _addToQueue() async {
    // VALIDASI INPUT: OPERATOR & QC WAJIB DIISI
    if (_opController.text.trim().isEmpty || _qcController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        backgroundColor: Colors.red,
        behavior: SnackBarBehavior.floating,
        content: Text("LENGKAPI DATA: Nama Operator dan QC wajib diisi!"),
      ));
      return;
    }

    final newBatch = _batchController.text;
    final printedIdx = _printQueue.indexWhere((e) => e.batch == newBatch && e.isPrinted);
    if (printedIdx != -1) {
      final item = _printQueue[printedIdx];
      bool? go = await showDialog<bool>(context: context, builder: (ctx) => ConfirmDialog(title: "DATA SUDAH ADA", message: "Batch ini sudah diproduksi sebanyak ${item.copies} Label. Lanjut ke Cetak Tambahan?", icon: Icons.history_rounded, color: Colors.indigo, confirmText: "KE CETAK ULANG"));
      if (go == true) { setState(() { _currentTabIndex = 1; _currentQueueSubTab = 1; }); showDialog(context: context, builder: (ctx) => ReprintModal(label: item, onPrint: (s,e,isL) => _executePrint(item, s, e, isL), onSyncOnly: (s,e) => _syncLabel(item, addCopies: (e - item.copies), isOnlySync: true))); }
      return;
    }
    final queueIdx = _printQueue.indexWhere((e) => e.batch == newBatch && !e.isPrinted);
    if (queueIdx != -1) {
      final existing = _printQueue[queueIdx];
      int oldP = existing.copies; int addP = int.tryParse(_labelCountController.text) ?? 1; int tot = oldP + addP;
      final db = await _dbService.database; await db.update('queue', {'copies': tot, 'device_model': _deviceModel}, where: 'id = ?', whereArgs: [existing.id]);
      await _dbService.recordLog("MERGE ANTREAN", "Batch ${existing.batch}: +$addP (Total: $tot)", deviceModel: _deviceModel); _showOverlayIcon(Icons.copy_all_rounded, Colors.white);
    } else {
      final p = int.tryParse(_labelCountController.text) ?? 1;
      final label = LabelData(item: _selectedItem, size: _sizeValueController.text, unit: _selectedUnit, batch: newBatch, machine: _selectedMachine, shift: _selectedShift, quantity: _qtyController.text, operator: _opController.text, qc: _qcController.text, date: DateFormat('dd-MM-yyyy').format(_selectedDate), time: _currentTime, copies: p, deviceModel: _deviceModel);
      await _dbService.addToLocalQueue(label.toMap()); _showOverlayIcon(Icons.add_task, Colors.greenAccent);
    }
    await _loadLocalQueue(); await _loadLocalLogs();
  }

  Future<void> _syncLabel(LabelData l, {int addCopies = 0, bool isOnlySync = false}) async {
    if (addCopies == 0 && !isOnlySync && !l.isSynced) {
      bool? confirm = await showDialog<bool>(context: context, builder: (ctx) => const ConfirmDialog(title: "SINKRONISASI", message: "Kirim reservasi batch ini ke Cloud? (0 Label)", icon: Icons.cloud_upload_rounded, color: Colors.blue));
      if (confirm != true) return;
    }
    int oldT = 0; final rIdx = _reports.indexWhere((r) => r['batch'] == l.batch);
    if (rIdx != -1) oldT = int.tryParse(_reports[rIdx]['copies']?.toString() ?? "0") ?? 0;
    Map<String, dynamic> data = l.toMap(); if (addCopies > 0) { data['copies'] = addCopies; } else if (!l.isPrinted) { data['copies'] = 0; }
    if (await _dbService.sendToRemoteDB(data)) {
      await _dbService.markAsSynced(l.id!); await _loadReports(); await _loadLocalQueue();
      String act = isOnlySync ? "SINKRON CLOUD" : (addCopies > 0 ? (l.isPrinted ? "UPDATE PRODUKSI" : "PRODUKSI PERDANA") : "SINKRON RENCANA");
      int finalT = oldT + addCopies;
      String detail = isOnlySync ? "Database: +$addCopies Label (Total: $finalT)" : (addCopies > 0 ? (l.isPrinted ? "Selesai mencetak tambahan: +$addCopies Label (Total: $finalT)" : "Mencetak perdana: $addCopies Label") : "Reservasi Batch (0 Label)");
      await _dbService.recordLog(act, "Batch ${l.batch}: $detail", deviceModel: _deviceModel); await _loadLocalLogs(); _showOverlayIcon(Icons.cloud_done, Colors.blueAccent);
    }
  }

  Future<void> _executePrint(LabelData l, int s, int e, bool isL) async {
    if (!await _askPrintConfirmation()) return;
    if (!kIsWeb && !_connected && !_isIpConnected) { _showOverlayIcon(Icons.link_off, Colors.redAccent); return; }
    int curT = 0; final hIdx = _printQueue.indexWhere((e) => e.batch == l.batch && e.isPrinted);
    if (hIdx != -1) curT = _printQueue[hIdx].copies;
    int rS = s; int rE = e; if (!l.isPrinted) { rS = curT + 1; rE = curT + l.deltaCount; }
    setState(() { _isProcessing = true; _shouldStopPrinting = false; });
    int actC = 0;
    try {
      for (int i = 0; i <= (rE - rS); i++) {
        if (_shouldStopPrinting) break;
        if (!kIsWeb) { await _bluetoothService.printProductionLabel(item: l.item, size: "${l.size} ${l.unit}", batch: l.batch, machine: l.machine, shift: l.shift, quantity: l.quantity, operator: l.operator, qc: l.qc, date: l.date, time: l.time, labelIndex: rS + i); await Future.delayed(const Duration(milliseconds: 1000)); } 
        else await Future.delayed(const Duration(milliseconds: 500));
        actC++;
      }
      if (actC > 0 && !_shouldStopPrinting) {
        if (!l.isPrinted) {
          if (hIdx != -1) {
            int newT = curT + actC;
            final db = await _dbService.database; await db.update('queue', {'copies': newT, 'device_model': _deviceModel}, where: 'id = ?', whereArgs: [_printQueue[hIdx].id]);
            await db.delete('queue', where: 'id = ?', whereArgs: [l.id]);
            await _syncLabel(_printQueue[hIdx]..copies = newT..deviceModel = _deviceModel, addCopies: actC);
          } else { await _dbService.markAsPrinted(l.id!); await _dbService.updateCopies(l.id!, actC); await _syncLabel(l..copies = curT + actC..deviceModel = _deviceModel, addCopies: actC); }
        } else if (isL) { int nt = l.copies + actC; await _dbService.updateCopies(l.id!, nt); await _syncLabel(l..copies = nt..deviceModel = _deviceModel, addCopies: actC); } 
        else { await _dbService.recordLog("GANTI RUSAK", "Batch ${l.batch}: no $rS-$rE", deviceModel: _deviceModel); await _loadLocalLogs(); }
      }
      _showOverlayIcon(Icons.check, Colors.greenAccent);
    } finally { setState(() => _isProcessing = false); await _loadLocalQueue(); await _loadReports(); }
  }

  Future<bool> _askPrintConfirmation() async {
    if (_skipPrintConfirmation) return true;
    bool check = false;
    bool? res = await showDialog<bool>(context: context, builder: (c) => StatefulBuilder(builder: (ctx, setM) => ConfirmDialog(title: "KONFIRMASI", message: "Lanjutkan cetak label fisik?", icon: Icons.print_rounded, color: Colors.indigo, confirmText: "MULAI CETAK", contentWidget: CheckboxListTile(title: const Text("Jangan tanya lagi", style: TextStyle(fontSize: 12)), value: check, onChanged: (v) => setM(() => check = v!), controlAffinity: ListTileControlAffinity.leading, contentPadding: EdgeInsets.zero, dense: true))));
    if (res == true && check) setState(() => _skipPrintConfirmation = true);
    return res ?? false;
  }

  void _showOverlayIcon(IconData i, Color c) { OverlayEntry o = OverlayEntry(builder: (ctx) => Center(child: Container(padding: const EdgeInsets.all(20), decoration: BoxDecoration(color: Colors.black54, borderRadius: BorderRadius.circular(20)), child: Icon(i, color: c, size: 80)))); Overlay.of(context).insert(o); Future.delayed(const Duration(milliseconds: 800), () => o.remove()); }

  Future<void> _handleResetLocalData() async {
    bool? res = await showDialog<bool>(context: context, builder: (ctx) => const ConfirmDialog(title: "RESET DATABASE", message: "Hapus semua data lokal secara permanen?", icon: Icons.delete_forever_rounded, color: Colors.red, confirmText: "YA, HAPUS"));
    if (res == true) {
      final pinC = TextEditingController();
      bool? auth = await showDialog<bool>(context: context, builder: (c) => AlertDialog(backgroundColor: Colors.grey[900], title: const Text("Admin Otoritas", style: TextStyle(color: Colors.white)), content: TextField(controller: pinC, maxLength: 4, textAlign: TextAlign.center, style: const TextStyle(color: Colors.white, fontSize: 24), decoration: const InputDecoration(filled: true, fillColor: Colors.white10)), actions: [TextButton(onPressed: ()=>Navigator.pop(c, false), child: const Text("BATAL")), TextButton(onPressed: ()=>Navigator.pop(c, true), child: const Text("OK"))]));
      if (auth == true && await _dbService.verifyResetPin(pinC.text)) { await _dbService.clearLocalData(); await _dbService.recordLog("RESET", "Basis data dibersihkan", deviceModel: _deviceModel); await _initAppData(); _showOverlayIcon(Icons.cleaning_services_rounded, Colors.greenAccent); }
    }
  }


  // --- UI BUILDERS ---
  @override
  Widget build(BuildContext context) {
    return Stack(children: [
      Scaffold(
        backgroundColor: Colors.grey[100], appBar: _buildAppBar(), 
        // Menggunakan Padding atau Container tanpa maxWidth agar konten memenuhi layar
        body: _buildBody(),
        extendBody: false, 
        bottomNavigationBar: _buildModernNav(), 
      ),
      if (_isProcessing) _buildElegantPrintOverlay(), 
      if (_isSyncing) _buildElegantSyncOverlay(), 
    ]);
  }

  // --- ELEGANT SYNC OVERLAY ---
  Widget _buildElegantSyncOverlay() {
    return BackdropFilter(
      filter: ImageFilter.blur(sigmaX: 8, sigmaY: 8),
      child: Container(
        color: Colors.black.withOpacity(0.7),
        child: Center(
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Stack(alignment: Alignment.center, children: [
              const SizedBox(width: 120, height: 120, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white24)),
              ScaleTransition(
                scale: Tween(begin: 0.8, end: 1.2).animate(CurvedAnimation(parent: _pulseController, curve: Curves.easeInOut)), // EFEK DENYUT
                child: const Icon(Icons.cloud_sync_rounded, color: Colors.white, size: 50)
              ),
            ]),
            const SizedBox(height: 30),
            const Text("SYNCHRONIZING DATABASE", style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w300, letterSpacing: 4)),
            const SizedBox(height: 10),
            const Text("Please wait, fetching latest master data...", style: TextStyle(color: Colors.white54, fontSize: 10)),
          ]),
        ),
      ),
    );
  }

  // --- ELEGANT PRINT OVERLAY (SIMPLE & ELEGANT) ---
  Widget _buildElegantPrintOverlay() {
    return BackdropFilter(
      filter: ImageFilter.blur(sigmaX: 5, sigmaY: 5), // EFEK BLUR SUTRA
      child: Container(
        color: Colors.indigo[900]!.withOpacity(0.8),
        child: Center(
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Stack(alignment: Alignment.center, children: [
              const SizedBox(width: 100, height: 100, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white24)), // RING DIAM
              ScaleTransition(
                scale: Tween(begin: 0.9, end: 1.1).animate(CurvedAnimation(parent: _pulseController, curve: Curves.easeInOut)),
                child: const Icon(Icons.print_rounded, color: Colors.white, size: 40)
              ),
            ]),
            const SizedBox(height: 30),
            const Text("PRINTING LABEL...", style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w300, letterSpacing: 4)),
            const SizedBox(height: 60),
            TextButton.icon(
              onPressed: () => setState(() => _shouldStopPrinting = true),
              icon: const Icon(Icons.stop_circle_outlined, color: Colors.white54, size: 16),
              label: const Text("EMERGENCY STOP", style: TextStyle(color: Colors.white54, fontSize: 10, fontWeight: FontWeight.bold)),
              style: TextButton.styleFrom(side: const BorderSide(color: Colors.white10), padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30))),
            )
          ]),
        ),
      ),
    );
  }

  // --- FAST & SMOOTH CURVED NAVBAR (FIXED BOUNDARY) ---
  Widget _buildModernNav() {
    return CurvedNavigationBar(
      index: _currentTabIndex,
      height: 75,
      backgroundColor: Colors.white,
      color: Colors.indigo[900]!,
      buttonBackgroundColor: Colors.white,
      animationDuration: const Duration(milliseconds: 350), 
      animationCurve: Curves.easeInOut,
      items: <Widget>[
        _navIcon(Icons.dashboard_customize_rounded, "TEMPLATE", 0),
        _navIcon(Icons.print_rounded, "ANTREAN", 1),
        _navIcon(Icons.add_box_rounded, "INPUT", 2),
        _navIcon(Icons.analytics_outlined, "LAPORAN", 3),
        _navIcon(Icons.history_edu_rounded, "LOG", 4),
      ],
      onTap: (index) {
        setState(() { _currentTabIndex = index; _selectedIds.clear(); });
        if(index==3)_loadReports(); if(index==4)_loadLocalLogs(); if(index==0)_loadTemplates();
      },
    );
  }
  Widget _navIcon(IconData icon, String label, int index) {
    bool active = _currentTabIndex == index;
    int qCount = _printQueue.where((e) => !e.isPrinted && e.date == DateFormat('dd-MM-yyyy').format(_selectedDate)).length;
    Widget iconWidget = Icon(icon, size: active ? 30 : 24, color: active ? Colors.indigo[900] : Colors.white70);
    if (index == 1 && qCount > 0) {
      iconWidget = Badge(label: Text("$qCount", style: const TextStyle(fontSize: 8, color: Colors.white, fontWeight: FontWeight.bold)), backgroundColor: Colors.red, child: iconWidget);
    }
    return Column(mainAxisSize: MainAxisSize.min, children: [
      iconWidget,
      if (!active) Text(label, style: const TextStyle(color: Colors.white70, fontSize: 7, fontWeight: FontWeight.bold))
    ]);
  }

  Widget _buildBody() {
    switch (_currentTabIndex) {
      case 0: return TemplateTab(templates: _templates, isLoading: _isLoadingTemplates, onSelect: _applyTemplate, onRefresh: _loadTemplates);
      case 1: return QueueTab(filteredQueue: _filteredQueue, subTab: _currentQueueSubTab, selectedIds: _selectedIds, onTabChange: (i)=>setState(()=>_currentQueueSubTab=i), onSearch: (v)=>setState(()=>_searchQuery=v), onShowDetail: (l)=>showDialog(context: context, builder: (ctx)=>DetailDialog(data: l, isReport: false, onEdit: () async { setState(() { _selectedItem = l.item; _selectedMachine = l.machine; _selectedShift = l.shift; _sizeValueController.text = l.size; _qtyController.text = l.quantity; _opController.text = l.operator; _qcController.text = l.qc; _selectedUnit = l.unit; _labelCountController.text = l.deltaCount.toString(); }); await _dbService.deleteFromLocalQueue(l.id!); await _loadLocalQueue(); Navigator.pop(ctx); setState(() { _currentTabIndex = 2; }); _updateRelatedData(); })), onPrint: (l) { if(_currentQueueSubTab == 0) _executePrint(l, 1, l.deltaCount, false); else showDialog(context: context, builder: (ctx)=>ReprintModal(label: l, onPrint: (s,e,isL)=>_executePrint(l,s,e,isL), onSyncOnly: (s,e) => _syncLabel(l, addCopies: (e - l.copies), isOnlySync: true))); }, onSync: (l) => _syncLabel(l), onDelete: (l) async { bool? res = await showDialog<bool>(context: context, builder: (ctx) => const ConfirmDialog(title: "HAPUS?", message: "Hapus batch ini?", icon: Icons.delete_rounded, color: Colors.red)); if(res == true) { await _dbService.deleteFromLocalQueue(l.id!); _loadLocalQueue(); } }, onDeleteMassal: (ids) async { bool? res = await showDialog<bool>(context: context, builder: (ctx) => const ConfirmDialog(title: "HAPUS MASAL?", message: "Hapus data terpilih?", icon: Icons.delete_forever_rounded, color: Colors.red)); if(res == true) { for(var id in ids) await _dbService.deleteFromLocalQueue(id); setState(()=>_selectedIds.clear()); _loadLocalQueue(); } }, onCancelSelect: ()=>setState(()=>_selectedIds.clear()));
      case 2: return InputTab(isWide: false, selectedItem: _selectedItem, selectedUnit: _selectedUnit, selectedMachine: _selectedMachine, selectedShift: _selectedShift, items: _items, units: _units, machines: _machines, shifts: _shifts, availableSizes: _availableSizes, availableQuantities: _availableQuantities, machineDetails: _machineDetails, sizeController: _sizeValueController, qtyController: _qtyController, labelCountController: _labelCountController, opController: _opController, qcController: _qcController, batchController: _batchController, currentTime: _currentTime, selectedDate: _selectedDate, onItemChanged: (v){ setState(()=>_selectedItem=v!); _updateAutoUnit(); _updateRelatedData(); }, onUnitChanged: (v)=>setState(()=>_selectedUnit=v!), onMachineChanged: (v){ setState(()=>_selectedMachine=v!); _updateRelatedData(); }, onShiftChanged: (v)=>setState(()=>_selectedShift=v!), onSave: _addToQueue, onGenerateBatch: _generateBatch, onRefresh: _refreshDatabase, isLoading: _isSyncing);
      case 3: return ReportTab(reports: _filteredReports, isLoading: _isLoadingReports, onRefresh: _loadReports, onShowDetail: (r)=>showDialog(context: context, builder: (ctx)=>DetailDialog(data: r, isReport: true)));
      case 4: return LogTab(logs: _filteredLogs, onRefresh: _loadLocalLogs);
      default: return Container();
    }
  }

  PreferredSizeWidget _buildAppBar() {
    String status = "OFFLINE"; Color sCol = Colors.redAccent;
    if (_connected) { status = "READY (BT)"; sCol = Colors.greenAccent; }
    else if (_isIpConnected) { status = "READY (IP)"; sCol = Colors.cyanAccent; }
    
    return AppBar(elevation: 0, backgroundColor: Colors.indigo[900], titleSpacing: 0, leading: const Icon(Icons.precision_manufacturing, color: Colors.white, size: 22), title: InkWell(onTap: () async { final picked = await showDatePicker(context: context, initialDate: _selectedDate, firstDate: DateTime(2020), lastDate: DateTime(2030)); if (picked != null) { setState(() { _selectedDate = picked; }); _initAppData(); } }, child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [const Text("PT AFTECH MAKASSAR INDONESIA", style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white)), Row(children: [const Icon(Icons.calendar_month, size: 10, color: Colors.white70), const SizedBox(width: 4), Text(DateFormat('dd-MM-yyyy').format(_selectedDate), style: const TextStyle(fontSize: 10, color: Colors.white70)), const Text(" | ", style: TextStyle(fontSize: 10, color: Colors.white70)), const Icon(Icons.access_time, size: 10, color: Colors.white70), const SizedBox(width: 4), Text(_currentTime, style: const TextStyle(fontSize: 10, color: Colors.white70))])])), actions: [InkWell(onTap: _showConnectionPicker, child: Container(margin: const EdgeInsets.symmetric(vertical: 10, horizontal: 8), padding: const EdgeInsets.symmetric(horizontal: 12), decoration: BoxDecoration(color: Colors.white12, borderRadius: BorderRadius.circular(20), border: Border.all(color: sCol)), child: Row(children: [Icon(_connected || _isIpConnected ? Icons.link : Icons.link_off, size: 14, color: sCol), const SizedBox(width: 6), Text(status, style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: sCol))]))), const SizedBox(width: 8)]);
  }

  void _showConnectionPicker() => showModalBottomSheet(
    context: context, isScrollControlled: true,
    shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(30))), 
    builder: (ctx) => StatefulBuilder(builder: (ctx, setM) => Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom),
      child: Container(
        padding: const EdgeInsets.all(24),
        decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(30))),
        child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
            const Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text("CONNECTIONS SETTING", style: TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: Colors.indigo, letterSpacing: 1)),
              Text("Industrial Connectivity Center", style: TextStyle(fontSize: 9, color: Colors.grey, fontWeight: FontWeight.bold)),
            ]),
            IconButton(icon: const Icon(Icons.close_rounded), onPressed: () => Navigator.pop(ctx))
          ]),
          const SizedBox(height: 25),
          const Text("PRINTER IP CONFIGURATION", style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.grey, letterSpacing: 1)),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            decoration: BoxDecoration(color: Colors.grey[50], borderRadius: BorderRadius.circular(15), border: Border.all(color: _isIpConnected ? Colors.cyan.withOpacity(0.3) : Colors.grey[200]!)),
            child: Row(children: [
              Icon(Icons.lan_rounded, color: _isIpConnected ? Colors.cyan : Colors.indigo, size: 20),
              const SizedBox(width: 12),
              Expanded(child: TextField(controller: _ipPrinterController, enabled: !_isIpConnected, decoration: const InputDecoration(border: InputBorder.none, hintText: "Enter IP Address", hintStyle: TextStyle(fontSize: 12, color: Colors.grey)))),
              ElevatedButton(
                onPressed: () {
                  setState(() => _isIpConnected = !_isIpConnected);
                  setM(() {});
                  ScaffoldMessenger.of(context).showSnackBar(SnackBar(backgroundColor: _isIpConnected ? Colors.cyan[900] : Colors.red[900], behavior: SnackBarBehavior.floating, content: Text(_isIpConnected ? "PRINTER TERHUBUNG (${_ipPrinterController.text})" : "KONEKSI IP DIPUTUS")));
                },
                style: ElevatedButton.styleFrom(backgroundColor: _isIpConnected ? Colors.red : Colors.indigo[900], foregroundColor: Colors.white, elevation: 0, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)), padding: const EdgeInsets.symmetric(horizontal: 12)),
                child: Text(_isIpConnected ? "DISCONNECT" : "CONNECT", style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold)),
              )
            ]),
          ),
          const SizedBox(height: 25),
          const Text("DATABASE SYNCHRONIZATION", style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.grey, letterSpacing: 1)),
          const SizedBox(height: 12),
          ElevatedButton.icon(
            onPressed: _isSyncing ? null : () {
              Navigator.pop(context); // Tutup modal dulu
              _refreshDatabase(); // Jalankan sync
            },
            icon: _isSyncing 
              ? const SizedBox(width: 15, height: 15, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.indigo)) 
              : const Icon(Icons.cloud_sync_rounded, size: 18),
            label: const Text("SINGKRON DATABASE", style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.grey[100], foregroundColor: Colors.indigo[900], minimumSize: const Size(double.infinity, 50), elevation: 0, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15), side: BorderSide(color: Colors.indigo.withOpacity(0.1)))),
          ),
          const SizedBox(height: 25),
          const Text("BLUETOOTH DEVICES", style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.grey)),
          const Divider(),
          if(_connected) ListTile(leading: const Icon(Icons.bluetooth_connected, color: Colors.green), title: Text(_selectedDevice?.name ?? "Unknown", style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold)), subtitle: const Text("Connected via Bluetooth", style: TextStyle(fontSize: 10, color: Colors.green)), trailing: TextButton(onPressed: () { _bluetoothService.disconnect().then((_) => setState(() => _connected = false)); Navigator.pop(ctx); }, child: const Text("DISCONNECT", style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 10))))
          else if(_devices.isEmpty) const Padding(padding: EdgeInsets.symmetric(vertical: 10), child: Text("Mencari Bluetooth...", style: TextStyle(fontSize: 11, color: Colors.grey)))
          else SizedBox(
            height: 150, // Tinggi tetap agar bisa di-scroll
            child: ListView.builder(
              padding: EdgeInsets.zero,
              itemCount: _devices.length,
              itemBuilder: (context, index) {
                final d = _devices[index];
                return ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.bluetooth, size: 20),
                  title: Text(d.name, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                  subtitle: Text(d.address ?? "", style: const TextStyle(fontSize: 9)),
                  onTap: () { setState(()=>_selectedDevice=d); _connect(); Navigator.pop(ctx); }
                );
              },
            ),
          ),
          const Divider(),
          ListTile(leading: const Icon(Icons.delete_forever, color: Colors.red), title: const Text("Reset Database Lokal", style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.red)), onTap: () { Navigator.pop(ctx); _handleResetLocalData(); }),
          const SizedBox(height: 20),
        ]),
      ),
    )),
  );

  Future<void> _scanDevices() async { if(kIsWeb) return; _devices = await _bluetoothService.getBluetoothDevices(); if(mounted) setState(() {}); }
  Future<void> _connect() async { if(_selectedDevice != null) { bool s = await _bluetoothService.connect(_selectedDevice!); setState(() => _connected = s); } }

  List<LabelData> get _filteredQueue { 
    String fUi = DateFormat('dd-MM-yyyy').format(_selectedDate); String fDb = DateFormat('yyyy-MM-dd').format(_selectedDate); String q = _searchQuery.toLowerCase(); 
    return _printQueue.where((e) {
      bool dateMatch = e.date == fUi || e.date == fDb;
      bool tabMatch = e.isPrinted == (_currentQueueSubTab == 1);
      bool searchMatch = q.isEmpty || e.batch.toLowerCase().contains(q) || e.item.toLowerCase().contains(q);
      return dateMatch && tabMatch && searchMatch;
    }).toList(); 
  }
  List<dynamic> get _filteredReports { String f1 = DateFormat('dd-MM-yyyy').format(_selectedDate); String f2 = DateFormat('yyyy-MM-dd').format(_selectedDate); String q = _searchQuery.toLowerCase(); return _reports.where((r) { if (r == null || r['production_date'] == null) return false; String d = r['production_date'].toString(); return (d.contains(f1) || d.contains(f2)) && (q.isEmpty || (r['batch']?.toString().toLowerCase() ?? "").contains(q)); }).toList(); }
  List<Map<String, dynamic>> get _filteredLogs { String f1 = DateFormat('dd-MM-yyyy').format(_selectedDate); String f2 = DateFormat('yyyy-MM-dd').format(_selectedDate); String q = _searchQuery.toLowerCase(); return _localLogs.where((l) { if (l == null || l['timestamp'] == null) return false; String d = l['timestamp'].toString(); return (d.contains(f1) || d.contains(f2)) && (q.isEmpty || (l['action']?.toString().toLowerCase() ?? "").contains(q)); }).toList(); }
}
