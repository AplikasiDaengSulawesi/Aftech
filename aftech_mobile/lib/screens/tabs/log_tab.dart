import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../widgets/mini_widgets.dart';

class LogTab extends StatelessWidget {
  final List<Map<String, dynamic>> logs;
  final VoidCallback onRefresh;

  const LogTab({super.key, required this.logs, required this.onRefresh});

  @override
  Widget build(BuildContext context) {
    return Column(children: [
      Padding(padding: const EdgeInsets.all(20), child: Row(children: [
        Container(padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: Colors.indigo.withOpacity(0.1), borderRadius: BorderRadius.circular(8)), child: const Icon(Icons.history_toggle_off_rounded, color: Colors.indigo, size: 20)),
        const SizedBox(width: 12),
        const Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text("LOG AKTIVITAS", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, letterSpacing: 0.5, color: Colors.indigo)),
          Text("Riwayat operasional perangkat hari ini", style: TextStyle(fontSize: 10, color: Colors.grey))
        ]),
        const Spacer(),
        IconButton(onPressed: onRefresh, icon: const Icon(Icons.refresh_rounded, color: Colors.indigo, size: 20))
      ])),
      Expanded(child: logs.isEmpty ? Center(child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [Icon(Icons.notes_rounded, size: 60, color: Colors.grey[300]), const SizedBox(height: 16), const Text("Belum ada aktivitas", style: TextStyle(color: Colors.grey, fontWeight: FontWeight.w500))])) : ListView.builder(padding: const EdgeInsets.fromLTRB(16, 0, 16, 20), itemCount: logs.length, itemBuilder: (context, index) {
        final log = logs[index]; String act = log['action']; String raw = log['details'] ?? ""; String device = "Unknown"; String details = raw;
        if (raw.startsWith("[") && raw.contains("]")) { int end = raw.indexOf("]"); device = raw.substring(1, end); details = raw.substring(end + 1).trim(); }
        
        // --- FIX ICON MERGE -> COPY ---
        IconData icon = Icons.info_outline; Color color = Colors.grey;
        if(act.contains("SINKRON")) { icon = Icons.cloud_done_rounded; color = Colors.blue; } 
        else if(act.contains("PRODUKSI")) { icon = Icons.print_rounded; color = Colors.indigo; } 
        else if(act.contains("RUSAK")) { icon = Icons.print_rounded; color = Colors.red; }
        else if(act.contains("MERGE")) { icon = Icons.copy_all_rounded; color = Colors.amber[700]!; } // IKON COPY UNTUK MERGE
        
        return Container(margin: const EdgeInsets.only(bottom: 12), padding: const EdgeInsets.all(16), decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(15), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 10, offset: const Offset(0, 2))], border: Border.all(color: color.withOpacity(0.1), width: 1)), child: Row(crossAxisAlignment: CrossAxisAlignment.center, children: [
          Container(width: 50, height: 50, decoration: BoxDecoration(color: color.withOpacity(0.08), borderRadius: BorderRadius.circular(15)), child: Icon(icon, color: color, size: 26)),
          const SizedBox(width: 16),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [Text(act, style: TextStyle(fontWeight: FontWeight.w900, fontSize: 13, color: color, letterSpacing: 0.5)), Text(DateFormat('HH:mm').format(DateTime.parse(log['timestamp'])), style: TextStyle(fontSize: 10, color: Colors.grey[400], fontWeight: FontWeight.bold))]),
            const SizedBox(height: 4),
            Text(details, style: const TextStyle(fontSize: 11, color: Colors.black87, height: 1.4)),
            const SizedBox(height: 8),
            SmallBadge(icon: Icons.smartphone_rounded, text: device)
          ]))
        ]));
      }))
    ]);
  }
}
