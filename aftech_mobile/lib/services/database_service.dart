import 'dart:convert';
import 'dart:io';
import 'package:sqflite/sqflite.dart';
import 'package:path/path.dart';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

class DatabaseService {
  static final DatabaseService instance = DatabaseService._init();
  static Database? _database;
  DatabaseService._init();

  final String baseUrl = "https://unsilent-ulrike-obsolescently.ngrok-free.dev/aftech/api";

  Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDB('aftech_production_v7.db'); // VERSI 7: SUPPORT LOCAL TEMPLATES
    return _database!;
  }

  Future<Database> _initDB(String filePath) async {
    final dbPath = await getDatabasesPath();
    final path = join(dbPath, filePath);
    return await openDatabase(path, version: 7, onCreate: _createDB, onUpgrade: _upgradeDB);
  }

  Future _createDB(Database db, int version) async {
    await db.execute('''CREATE TABLE queue (id INTEGER PRIMARY KEY AUTOINCREMENT, item TEXT, size TEXT, unit TEXT, batch TEXT, machine TEXT, shift TEXT, quantity TEXT, operator TEXT, qc TEXT, production_date TEXT, production_time TEXT, copies INTEGER, is_printed INTEGER DEFAULT 0, is_synced INTEGER DEFAULT 0, device_model TEXT)''');
    await db.execute('''CREATE TABLE activity_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, action TEXT, details TEXT, timestamp TEXT)''');
    
    // TABEL LOKAL TEMPLATE
    await db.execute('''CREATE TABLE master_templates (id INTEGER PRIMARY KEY, template_name TEXT, item TEXT, size TEXT, unit TEXT, machine TEXT, shift TEXT, quantity TEXT)''');
  }

  Future _upgradeDB(Database db, int oldVersion, int newVersion) async {
    if (oldVersion < 7) {
      await db.execute('''CREATE TABLE IF NOT EXISTS master_templates (id INTEGER PRIMARY KEY, template_name TEXT, item TEXT, size TEXT, unit TEXT, machine TEXT, shift TEXT, quantity TEXT)''');
    }
  }

  // --- LOCAL OPERATIONS ---
  Future<int> addToLocalQueue(Map<String, dynamic> data) async {
    final db = await instance.database;
    return await db.insert('queue', data);
  }

  Future<List<Map<String, dynamic>>> getLocalQueue() async {
    final db = await instance.database;
    return await db.query('queue', orderBy: 'id DESC');
  }

  Future<int> deleteFromLocalQueue(int id) async {
    final db = await instance.database;
    return await db.delete('queue', where: 'id = ?', whereArgs: [id]);
  }

  Future<int> markAsPrinted(int id) async {
    final db = await instance.database;
    return await db.update('queue', {'is_printed': 1}, where: 'id = ?', whereArgs: [id]);
  }

  Future<int> markAsSynced(int id) async {
    final db = await instance.database;
    return await db.update('queue', {'is_synced': 1}, where: 'id = ?', whereArgs: [id]);
  }

  Future<int> updateCopies(int id, int copies) async {
    final db = await instance.database;
    return await db.update('queue', {'copies': copies}, where: 'id = ?', whereArgs: [id]);
  }

  Future<void> clearLocalData() async {
    final db = await instance.database;
    await db.delete('queue');
    await db.delete('activity_logs');
    await db.delete('master_templates');
  }

  Future<void> recordLog(String action, String details, {String? deviceModel}) async {
    final db = await instance.database;
    String model = deviceModel ?? "Unknown Device";
    String finalDetails = "[$model] $details";
    await db.insert('activity_logs', {
      'action': action,
      'details': finalDetails,
      'timestamp': DateTime.now().toIso8601String()
    });
  }

  Future<List<Map<String, dynamic>>> getLocalLogs() async {
    final db = await instance.database;
    return await db.query('activity_logs', orderBy: 'timestamp DESC', limit: 100);
  }

  // --- TEMPLATE LOKAL ---
  Future<List<Map<String, dynamic>>> getLocalTemplates() async {
    final db = await instance.database;
    return await db.query('master_templates', orderBy: 'template_name ASC');
  }

  // --- SYNC ENGINE (NOW WITH TEMPLATES) ---
  Future<bool> syncMasterData() async {
    try {
      debugPrint("🚀 [SYNC] Menghubungi Server...");
      // 1. Ambil Master Data (Items, Machines, etc)
      final resMaster = await http.get(Uri.parse("$baseUrl/get_master_data.php")).timeout(const Duration(seconds: 15));
      
      // 2. Ambil Master Templates
      final resTemp = await http.get(Uri.parse("$baseUrl/get_templates.php")).timeout(const Duration(seconds: 15));
      
      if (resMaster.statusCode == 200 && resTemp.statusCode == 200) {
        final db = await instance.database;
        final List templates = jsonDecode(resTemp.body);
        
        await db.transaction((txn) async {
          // Update Antrean (Healing logic)
          await txn.delete('queue', where: 'is_printed = 0 AND id NOT IN (SELECT id FROM queue WHERE is_synced = 1)');
          
          // Update Template Lokal
          await txn.delete('master_templates');
          for (var t in templates) {
            await txn.insert('master_templates', {
              'id': t['id'],
              'template_name': t['template_name'],
              'item': t['item'],
              'size': t['size'],
              'unit': t['unit'],
              'machine': t['machine'],
              'shift': t['shift'],
              'quantity': t['quantity'],
            });
          }
        });
        debugPrint("✅ [SYNC] Database Lokal Diperbarui.");
        return true;
      }
    } catch (e) { debugPrint("❌ [SYNC ERROR]: $e"); }
    return false;
  }

  // --- DATA FETCH HELPERS ---
  Future<List<Map<String, dynamic>>> getItemsWithUnits() async {
    try {
      final res = await http.get(Uri.parse("$baseUrl/get_master_data.php"));
      if(res.statusCode == 200) return List<Map<String, dynamic>>.from(jsonDecode(res.body)['items']);
    } catch(e) {}
    return [];
  }

  Future<List<Map<String, dynamic>>> getMachinesWithStatus() async {
    try {
      final res = await http.get(Uri.parse("$baseUrl/get_master_data.php"));
      if(res.statusCode == 200) return List<Map<String, dynamic>>.from(jsonDecode(res.body)['machines']);
    } catch(e) {}
    return [];
  }

  Future<List<String>> getMasterData(String type) async {
    try {
      final res = await http.get(Uri.parse("$baseUrl/get_master_data.php"));
      if(res.statusCode == 200) return List<String>.from(jsonDecode(res.body)[type]);
    } catch(e) {}
    return [];
  }

  Future<List<String>> getRelatedSizes(String itemName) async {
    try {
      final res = await http.get(Uri.parse("$baseUrl/get_master_data.php"));
      if(res.statusCode == 200) {
        final List sizes = jsonDecode(res.body)['sizes'];
        return sizes.where((s) => s['parent_item'] == itemName).map((s) => s['size_value'].toString()).toList();
      }
    } catch(e) {}
    return [];
  }

  Future<List<String>> getRelatedQuantities(String machineName) async {
    try {
      final res = await http.get(Uri.parse("$baseUrl/get_master_data.php"));
      if(res.statusCode == 200) {
        final List qtys = jsonDecode(res.body)['quantities'];
        return qtys.where((q) => q['parent_machine'] == machineName).map((q) => q['qty_value'].toString()).toList();
      }
    } catch(e) {}
    return [];
  }

  // --- REMOTE SYNC ---
  Future<bool> sendToRemoteDB(Map<String, dynamic> data) async {
    try {
      final response = await http.post(Uri.parse("$baseUrl/save_label.php"), headers: {"Content-Type": "application/json"}, body: jsonEncode(data)).timeout(const Duration(seconds: 15));
      return response.statusCode == 200;
    } catch (e) { return false; }
  }

  Future<bool> verifyResetPin(String pin) async => pin == "0503";

  Future<List<dynamic>> fetchRemoteReports() async {
    try {
      final response = await http.get(Uri.parse("$baseUrl/get_reports.php")).timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) return jsonDecode(response.body);
    } catch (e) {}
    return [];
  }
}
