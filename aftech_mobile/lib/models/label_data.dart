class LabelData {
  int? id;
  String item;
  String size;
  String unit;
  String batch;
  String machine;
  String shift;
  String quantity;
  String operator;
  String qc;
  String date;
  String time;
  int copies;
  bool isPrinted;
  bool isSynced;
  String? deviceModel;

  LabelData({
    this.id, required this.item, required this.size, required this.unit,
    required this.batch, required this.machine, required this.shift,
    required this.quantity, required this.operator, required this.qc,
    required this.date, required this.time, this.copies = 0,
    this.isPrinted = false, this.isSynced = false, this.deviceModel
  });

  Map<String, dynamic> toMap() {
    return {
      'id': id, 'item': item, 'size': size, 'unit': unit, 'batch': batch,
      'machine': machine, 'shift': shift, 'quantity': quantity,
      'operator': operator, 'qc': qc, 'production_date': date,
      'production_time': time, 'copies': copies,
      'is_printed': isPrinted ? 1 : 0, 'is_synced': isSynced ? 1 : 0,
      'device_model': deviceModel
    };
  }

  factory LabelData.fromMap(Map<String, dynamic> map) {
    return LabelData(
      id: map['id'], item: map['item'] ?? '', size: map['size'] ?? '',
      unit: map['unit'] ?? '', batch: map['batch'] ?? '',
      machine: map['machine'] ?? '', shift: map['shift'] ?? '',
      quantity: map['quantity'] ?? '', operator: map['operator'] ?? '',
      qc: map['qc'] ?? '', date: map['production_date'] ?? '',
      time: map['production_time'] ?? '', copies: map['copies'] ?? 0,
      isPrinted: (map['is_printed'] ?? 0) == 1,
      isSynced: (map['is_synced'] ?? 0) == 1,
      deviceModel: map['device_model']
    );
  }

  // FIX: Tambahkan Setter agar UI bisa mengubah jumlah copies via alias deltaCount
  int get deltaCount => copies;
  set deltaCount(int value) => copies = value;
}
