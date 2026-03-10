import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../models/label_data.dart';

class DetailDialog extends StatelessWidget {
  final dynamic data;
  final bool isReport;
  final VoidCallback? onEdit;

  const DetailDialog({super.key, required this.data, required this.isReport, this.onEdit});

  @override
  Widget build(BuildContext context) {
    bool isQueue = data is LabelData;
    
    // Konversi ke Map agar akses key lebih konsisten dan aman
    Map<String, dynamic> mappedData = isQueue ? data.toMap() : Map<String, dynamic>.from(data);

    // Fungsi Pembantu: Pastikan tidak tampil blank jika data kosong
    String safeVal(dynamic val) {
      if (val == null || val.toString().trim().isEmpty || val.toString() == 'null') return '-';
      return val.toString();
    }

    String batch = safeVal(mappedData['batch']);
    String item = safeVal(mappedData['item']);
    String size = safeVal(mappedData['size']);
    String unit = safeVal(mappedData['unit']);
    String machine = safeVal(mappedData['machine']);
    String shift = safeVal(mappedData['shift']);
    String op = safeVal(mappedData['operator']);
    String qc = safeVal(mappedData['qc']);
    
    // Handle Tanggal & Jam secara spesifik
    String date = safeVal(mappedData['production_date']);
    String time = safeVal(mappedData['production_time']);
    String total = "${mappedData['copies'] ?? 0} Label";

    bool canEdit = isQueue && (mappedData['is_printed'] == 0) && (mappedData['is_synced'] == 0);

    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      titlePadding: EdgeInsets.zero,
      title: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
        decoration: BoxDecoration(color: Colors.indigo[900], borderRadius: const BorderRadius.vertical(top: Radius.circular(20))),
        child: Row(children: [
          Icon(isReport ? Icons.analytics_rounded : Icons.layers_rounded, color: Colors.white, size: 20),
          const SizedBox(width: 10),
          Text(isReport ? "Detail Laporan" : "Detail Antrean", style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
        ]),
      ),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            _row(Icons.qr_code_rounded, "Batch", batch),
            _row(Icons.inventory_2_outlined, "Produk", item),
            _row(Icons.straighten_rounded, "Ukuran", "$size $unit"),
            _row(Icons.precision_manufacturing_outlined, "Mesin", machine),
            _row(Icons.schedule_rounded, "Shift Kerja", shift.toUpperCase()),
            _row(Icons.person_outline, "Operator", op.toUpperCase()),
            _row(Icons.fact_check_outlined, "QC Check", qc.toUpperCase()),
            _row(Icons.calendar_month_rounded, "Tanggal", date),
            _row(Icons.access_time_rounded, "Jam Produksi", "$time WITA"),
            const Divider(height: 20),
            _row(Icons.print_rounded, "Total Cetak", total),
          ],
        ),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text("TUTUP", style: TextStyle(color: Colors.grey, fontWeight: FontWeight.bold))),
        if (canEdit) ElevatedButton.icon(onPressed: onEdit, icon: const Icon(Icons.edit_note_rounded, size: 18), label: const Text("EDIT"), style: ElevatedButton.styleFrom(backgroundColor: Colors.indigo, foregroundColor: Colors.white, elevation: 0, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)))),
      ],
    );
  }

  Widget _row(IconData icon, String label, String value) {
    return Padding(padding: const EdgeInsets.symmetric(vertical: 6), child: Row(children: [
      Icon(icon, size: 14, color: Colors.indigo.withOpacity(0.3)),
      const SizedBox(width: 10),
      SizedBox(width: 60, child: Text(label, style: const TextStyle(fontSize: 9, color: Colors.grey, fontWeight: FontWeight.bold))),
      Expanded(child: Text(value, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 12, color: Colors.black87))),
    ]));
  }
}

class ReprintModal extends StatefulWidget {
  final LabelData label;
  final Function(int start, int end, bool isLanjutan) onPrint;
  final Function(int start, int end)? onSyncOnly;

  const ReprintModal({super.key, required this.label, required this.onPrint, this.onSyncOnly});

  @override
  State<ReprintModal> createState() => _ReprintModalState();
}

class _ReprintModalState extends State<ReprintModal> {
  int _mode = 0; // 0: Lanjutan, 1: Ganti Rusak
  final startC = TextEditingController();
  final endC = TextEditingController();

  @override
  void initState() { super.initState(); _updateFields(); }

  void _updateFields() {
    if (_mode == 0) {
      startC.text = (widget.label.copies + 1).toString();
      endC.text = (widget.label.copies + 1).toString();
    } else {
      startC.text = "1";
      endC.text = "1";
    }
  }

  @override
  Widget build(BuildContext context) {
    int s = int.tryParse(startC.text) ?? 0;
    int e = int.tryParse(endC.text) ?? 0;
    int count = (e - s + 1);
    
    // LOGIC VALIDASI REALTIME
    bool isValid = e >= s && s > 0;
    if (_mode == 1) { // Ganti Rusak
      if (e > widget.label.copies) isValid = false;
    } else { // Lanjutan
      if (s != widget.label.copies + 1) isValid = false;
    }

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Container(
        width: 320,
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(children: [
              Icon(Icons.settings_suggest_rounded, color: Colors.indigo[900], size: 22),
              const SizedBox(width: 10),
              const Text("PENGATURAN CETAK", style: TextStyle(fontWeight: FontWeight.w900, fontSize: 14, letterSpacing: 0.5)),
            ]),
            const SizedBox(height: 15),
            // INFO RINGKAS BATCH
            Container(
              padding: const EdgeInsets.all(12),
              width: double.infinity,
              decoration: BoxDecoration(color: Colors.indigo.withOpacity(0.05), borderRadius: BorderRadius.circular(12)),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(widget.label.batch, style: const TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Colors.grey)),
                const SizedBox(height: 4),
                Text("${widget.label.item} • ${widget.label.size}${widget.label.unit}", style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w900, color: Colors.black87)),
                const Divider(height: 15),
                Row(children: [
                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    const Text("SHIFT KERJA", style: TextStyle(fontSize: 7, fontWeight: FontWeight.bold, color: Colors.grey)),
                    Text(widget.label.shift, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.indigo)),
                  ])),
                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    const Text("QC CHECK", style: TextStyle(fontSize: 7, fontWeight: FontWeight.bold, color: Colors.grey)),
                    Text(widget.label.qc.toUpperCase(), style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.indigo)),
                  ])),
                ]),
              ]),
            ),
            const SizedBox(height: 20),
            Container(
              padding: const EdgeInsets.all(3),
              decoration: BoxDecoration(color: Colors.grey[100], borderRadius: BorderRadius.circular(10)),
              child: Row(children: [
                _modeBtn(0, "LANJUTAN"),
                _modeBtn(1, "GANTI RUSAK"),
              ]),
            ),
            const SizedBox(height: 20),
            Row(children: [
              Expanded(child: _inputField("DARI NO:", startC, _mode == 1)),
              const SizedBox(width: 12),
              Expanded(child: _inputField("HINGGA NO:", endC, true)),
            ]),
            const SizedBox(height: 15),
            
            // FEEDBACK VISUAL REALTIME
            AnimatedContainer(
              duration: const Duration(milliseconds: 300),
              padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 16),
              width: double.infinity,
              decoration: BoxDecoration(
                color: isValid ? Colors.blue.withOpacity(0.1) : Colors.red.withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: isValid ? Colors.blue.withOpacity(0.3) : Colors.red.withOpacity(0.3))
              ),
              child: Row(children: [
                Icon(isValid ? Icons.check_circle_rounded : Icons.error_rounded, size: 14, color: isValid ? Colors.blue[900] : Colors.red[900]),
                const SizedBox(width: 8),
                Expanded(child: Text(isValid ? "Total: $count Label akan dicetak" : (_mode == 0 ? "Mulai harus no ${widget.label.copies+1}" : "Maksimal sampai no ${widget.label.copies}"), style: TextStyle(fontWeight: FontWeight.bold, fontSize: 10, color: isValid ? Colors.blue[900] : Colors.red[900]))),
              ]),
            ),
            const SizedBox(height: 20),
            
            if (_mode == 0) Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: OutlinedButton.icon(
                onPressed: !isValid ? null : () { Navigator.pop(context); widget.onSyncOnly?.call(s, e); },
                icon: const Icon(Icons.cloud_sync_rounded, size: 16),
                label: const Text("SINKRON CLOUD SAJA", style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size(double.infinity, 42),
                  side: BorderSide(color: isValid ? Colors.indigo : Colors.grey[300]!),
                  foregroundColor: Colors.indigo,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))
                ),
              ),
            ),
            ElevatedButton(
              onPressed: !isValid ? null : () { Navigator.pop(context); widget.onPrint(s, e, _mode == 0); },
              style: ElevatedButton.styleFrom(
                backgroundColor: isValid ? Colors.redAccent : Colors.grey[300],
                foregroundColor: Colors.white,
                minimumSize: const Size(double.infinity, 48),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                elevation: 0
              ),
              child: Text("MULAI CETAK", style: TextStyle(fontWeight: FontWeight.w900, fontSize: 13, color: isValid ? Colors.white : Colors.grey[500])),
            ),
            TextButton(onPressed: () => Navigator.pop(context), child: const Text("BATAL", style: TextStyle(color: Colors.grey, fontSize: 11))),
          ],
        ),
      ),
    );
  }

  Widget _modeBtn(int m, String label) {
    bool active = _mode == m;
    return Expanded(child: InkWell(
      onTap: () { setState(() { _mode = m; _updateFields(); }); },
      child: Container(padding: const EdgeInsets.symmetric(vertical: 10), decoration: BoxDecoration(color: active ? Colors.white : Colors.transparent, borderRadius: BorderRadius.circular(8), boxShadow: active ? [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 2)] : null), child: Center(child: Text(label, style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: active ? Colors.indigo[900] : Colors.grey)))),
    ));
  }

  Widget _inputField(String label, TextEditingController ctrl, bool enabled) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(label, style: const TextStyle(fontSize: 8, fontWeight: FontWeight.bold, color: Colors.grey)),
      const SizedBox(height: 4),
      SizedBox(height: 42, child: TextField(
        controller: ctrl, 
        enabled: enabled, 
        onChanged: (v) => setState(() {}), // TRIGGER VALIDASI REALTIME
        keyboardType: TextInputType.number, 
        inputFormatters: [FilteringTextInputFormatter.digitsOnly], 
        textAlign: TextAlign.center, 
        style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18, color: enabled ? Colors.black : Colors.grey), 
        decoration: InputDecoration(
          isDense: true, 
          filled: true, 
          fillColor: enabled ? Colors.white : Colors.grey[100], 
          contentPadding: const EdgeInsets.symmetric(vertical: 10),
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: Colors.grey[300]!)),
          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: Colors.grey[300]!)),
          focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Colors.indigo, width: 2)),
        )
      )),
    ]);
  }
}
