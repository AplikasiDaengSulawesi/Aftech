import 'package:flutter/material.dart';
import '../../widgets/mini_widgets.dart';

class ReportTab extends StatelessWidget {
  final List<dynamic> reports;
  final bool isLoading;
  final VoidCallback onRefresh;
  final Function(dynamic data) onShowDetail;

  const ReportTab({super.key, required this.reports, required this.isLoading, required this.onRefresh, required this.onShowDetail});

  @override
  Widget build(BuildContext context) {
    int totalLabels = reports.fold(0, (sum, item) => sum + (int.tryParse(item['copies'].toString()) ?? 0));
    
    return Column(children: [
      Padding(padding: const EdgeInsets.all(20), child: Row(children: [
        Container(padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: Colors.indigo.withOpacity(0.1), borderRadius: BorderRadius.circular(8)), child: const Icon(Icons.analytics_rounded, color: Colors.indigo, size: 20)),
        const SizedBox(width: 12),
        Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          const Text("LAPORAN PRODUKSI", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, letterSpacing: 0.5, color: Colors.indigo)),
          Text("Total: $totalLabels Label dari ${reports.length} Batch", style: const TextStyle(fontSize: 10, color: Colors.grey))
        ]),
        const Spacer(),
        IconButton(onPressed: onRefresh, icon: const Icon(Icons.refresh_rounded, color: Colors.indigo, size: 20))
      ])),
      Expanded(child: isLoading ? const Center(child: CircularProgressIndicator(strokeWidth: 2)) : reports.isEmpty ? Center(child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [Icon(Icons.analytics_outlined, size: 60, color: Colors.grey[300]), const SizedBox(height: 16), const Text("Belum ada data produksi", style: TextStyle(color: Colors.grey, fontWeight: FontWeight.w500))])) : ListView.builder(padding: const EdgeInsets.fromLTRB(16, 0, 16, 20), itemCount: reports.length, itemBuilder: (context, index) {
        final r = reports[index]; int cp = int.tryParse(r['copies'].toString()) ?? 0; bool isDone = cp > 0; Color color = isDone ? Colors.blue : Colors.orange;
        return Container(margin: const EdgeInsets.only(bottom: 12), decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(15), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 10, offset: const Offset(0, 2))], border: Border.all(color: color.withOpacity(0.1), width: 1)), child: InkWell(borderRadius: BorderRadius.circular(15), onTap: () => onShowDetail(r), child: Padding(padding: const EdgeInsets.all(16), child: Row(crossAxisAlignment: CrossAxisAlignment.center, children: [
          Container(width: 50, height: 50, decoration: BoxDecoration(color: color.withOpacity(0.08), borderRadius: BorderRadius.circular(15)), child: Icon(isDone ? Icons.task_alt_rounded : Icons.pending_rounded, color: color, size: 26)),
          const SizedBox(width: 16),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(r['batch'] ?? "", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 10, color: Colors.grey[500], letterSpacing: 0.5)), const SizedBox(height: 2), Text("${r['item']} • ${r['size']}${r['unit']}", style: const TextStyle(fontSize: 13, color: Colors.black87, fontWeight: FontWeight.w900)), const SizedBox(height: 8), Row(children: [SmallBadge(icon: Icons.precision_manufacturing_outlined, text: r['machine'] ?? "-"), const SizedBox(width: 6), SmallBadge(icon: Icons.person_outline, text: r['operator'] ?? "-")])])),
          Column(crossAxisAlignment: CrossAxisAlignment.end, children: [Text(r['production_time'] ?? "", style: TextStyle(fontSize: 10, color: Colors.grey[400], fontWeight: FontWeight.bold)), const SizedBox(height: 6), Text(isDone ? "$cp" : "0", style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: color, height: 1)), Text(isDone ? "LABEL DICETAK" : "LABEL BELUM ADA", style: TextStyle(fontSize: 7, fontWeight: FontWeight.bold, color: color))])
        ]))));
      }))
    ]);
  }
}
