import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:flutter_pos_printer_platform_image_3/flutter_pos_printer_platform_image_3.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:qr/qr.dart';

class BluetoothService {
  var printerManager = PrinterManager.instance;
  StreamSubscription? _subscription;
  List<PrinterDevice> devices = [];

  Future<bool> checkPermissions() async {
    if (kIsWeb) return false;
    Map<Permission, PermissionStatus> statuses = await [
      Permission.bluetooth,
      Permission.bluetoothConnect,
      Permission.bluetoothScan,
      Permission.location,
    ].request();
    return statuses.values.every((status) => status.isGranted);
  }

  Future<List<PrinterDevice>> getBluetoothDevices() async {
    if (kIsWeb) return [];
    bool hasPermission = await checkPermissions();
    if (!hasPermission) return [];
    devices.clear();
    Completer<List<PrinterDevice>> completer = Completer();
    _subscription = printerManager
        .discovery(type: PrinterType.bluetooth)
        .listen((device) {
          if (!devices.any((element) => element.address == device.address)) {
            devices.add(device);
          }
        });
    Future.delayed(const Duration(seconds: 3), () {
      _subscription?.cancel();
      completer.complete(devices);
    });
    return completer.future;
  }

  Future<bool> connect(PrinterDevice device) async {
    try {
      return await printerManager.connect(
        type: PrinterType.bluetooth,
        model: BluetoothPrinterInput(
          name: device.name,
          address: device.address!,
          isBle: false,
        ),
      );
    } catch (e) {
      print("Error connecting: $e");
      return false;
    }
  }

  Future<void> disconnect() async {
    await printerManager.disconnect(type: PrinterType.bluetooth);
  }

  Future<void> printProductionLabel({
    required String item,
    required String size,
    required String batch,
    required String machine,
    required String shift,
    required String quantity,
    required String operator,
    required String qc,
    required String date,
    required String time,
    int labelIndex = 1,
  }) async {
    String tspl = "";
    tspl += "SIZE 78 mm, 100 mm\r\n";
    tspl += "GAP 3 mm, 0 mm\r\n";
    tspl += "DIRECTION 1,0\r\n";
    tspl += "REFERENCE 0,0\r\n";
    tspl += "CLS\r\n";

    // 1. FORMAT TANGGAL INDONESIA (WITA)
    List<String> months = ["", "JANUARI", "FEBRUARI", "MARET", "APRIL", "MEI", "JUNI", "JULI", "AGUSTUS", "SEPTEMBER", "OKTOBER", "NOVEMBER", "DESEMBER"];
    List<String> dParts = date.contains('-') ? date.split('-') : date.split('/');
    String displayDate = date;
    if (dParts.length == 3) {
      int? mIdx;
      if (int.tryParse(dParts[1]) != null && int.parse(dParts[1]) <= 12) {
        mIdx = int.parse(dParts[1]);
        displayDate = "${dParts[0]} ${months[mIdx]} ${dParts[2]}";
      } else if (int.tryParse(dParts[0]) != null && int.parse(dParts[0]) <= 12) {
        mIdx = int.parse(dParts[0]);
        displayDate = "${dParts[1]} ${months[mIdx]} ${dParts[2]}";
      }
    }
    String fullDateTime = "$displayDate | $time WITA";

    // Header Tanggal
    tspl += "TEXT 30,25,\"2\",0,1,1,\"$fullDateTime\"\r\n";
    
    // AFTECH PRODUCTION Vertikal (Magnifikasi 3,3)
    tspl += "TEXT 560,160,\"3\",90,3,3,\"AFTECH\"\r\n";

    // 2. MANUAL QR RENDERING (OPTION 2: NO PADDING, CUSTOM SIZE)
    String qrData = "$labelIndex-$batch";
    final qrValidationResult = QrValidator.validate(
      data: qrData,
      version: QrVersions.auto,
      errorCorrectionLevel: QrErrorCorrectLevel.L,
    );

    if (qrValidationResult.status == QrValidationStatus.valid) {
      final qrCode = qrValidationResult.qrCode!;
      final qrImage = QrImage(qrCode);

      // Target lebar ~450 dots (70% lebar label)
      int blockSize = (450 / qrImage.moduleCount).floor();
      int startX = 30;
      int startY = 60;

      for (int x = 0; x < qrImage.moduleCount; x++) {
        for (int y = 0; y < qrImage.moduleCount; y++) {
          if (qrImage.isDark(y, x)) {
            tspl += "BAR ${startX + (x * blockSize)},${startY + (y * blockSize)},$blockSize,$blockSize\r\n";
          }
        }
      }
    }

    // 3. DETAIL PRODUKSI (DI BAWAH QR)
    int yStart = 535; 
    tspl += "TEXT 30,$yStart,\"3\",0,2,2,\"$item $size\"\r\n";
    tspl += "TEXT 30,${yStart + 50},\"3\",0,2,2,\"${machine.toUpperCase()}\"\r\n";
    tspl += "TEXT 30,${yStart + 100},\"3\",0,2,2,\"$shift\"\r\n";
    tspl += "TEXT 30,${yStart + 150},\"3\",0,2,2,\"${operator.toUpperCase()} / ${qc.toUpperCase()}\"\r\n";
    tspl += "TEXT 30,${yStart + 200},\"2\",0,1,1,\"Batch: $batch\"\r\n";

    tspl += "PRINT 1,1\r\n";

    List<int> bytes = latin1.encode(tspl);
    await printerManager.send(
      type: PrinterType.bluetooth,
      bytes: bytes.toList(),
    );
  }
}
