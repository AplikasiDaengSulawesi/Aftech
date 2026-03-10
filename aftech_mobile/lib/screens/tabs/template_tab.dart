import 'package:flutter/material.dart';
import '../../widgets/mini_widgets.dart';

class TemplateTab extends StatelessWidget {
  final List<dynamic> templates;
  final bool isLoading;
  final Function(Map<String, dynamic>) onSelect;
  final VoidCallback onRefresh;

  const TemplateTab({
    super.key, required this.templates, required this.isLoading, 
    required this.onSelect, required this.onRefresh
  });

  @override
  Widget build(BuildContext context) {
    return Column(children: [
      // HEADER SERASI DENGAN LAPORAN/LOG (EXACT MATCH)
      Padding(padding: const EdgeInsets.all(20), child: Row(children: [
        Container(padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: Colors.indigo.withOpacity(0.1), borderRadius: BorderRadius.circular(8)), child: const Icon(Icons.dashboard_customize_rounded, color: Colors.indigo, size: 20)),
        const SizedBox(width: 12),
        const Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text("TEMPLATE PRODUKSI", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, letterSpacing: 0.5, color: Colors.indigo)),
          Text("Format produksi cepat (Sekali Klik)", style: TextStyle(fontSize: 10, color: Colors.grey))
        ]),
        const Spacer(),
        IconButton(onPressed: onRefresh, icon: const Icon(Icons.refresh_rounded, color: Colors.indigo, size: 20))
      ])),

      Expanded(
        child: isLoading 
        ? const Center(child: CircularProgressIndicator(strokeWidth: 2))
        : templates.isEmpty 
        ? _buildEmpty()
        : GridView.builder(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 20),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2, crossAxisSpacing: 12, mainAxisSpacing: 12, childAspectRatio: 1.1
            ),
            itemCount: templates.length,
            itemBuilder: (ctx, i) {
              final t = templates[i];
              return Container(
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(15),
                  border: Border.all(color: Colors.indigo.withOpacity(0.05), width: 1),
                  boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 10, offset: const Offset(0, 2))]
                ),
                child: InkWell(
                  onTap: () => onSelect(t),
                  borderRadius: BorderRadius.circular(15),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                        const Icon(Icons.bolt_rounded, color: Colors.amber, size: 18),
                        Container(padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2), decoration: BoxDecoration(color: Colors.indigo.withOpacity(0.05), borderRadius: BorderRadius.circular(4)), child: Text(t['shift'] ?? '-', style: const TextStyle(fontSize: 7, fontWeight: FontWeight.bold, color: Colors.indigo))),
                      ]),
                      const Spacer(),
                      Text(t['template_name'] ?? 'Template', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.black87, height: 1.2), maxLines: 2, overflow: TextOverflow.ellipsis),
                      const SizedBox(height: 6),
                      Text("${t['item']} • ${t['size']}${t['unit']}", style: const TextStyle(fontSize: 8, color: Colors.grey, fontWeight: FontWeight.w500)),
                      const SizedBox(height: 2),
                      Text(t['machine'] ?? '-', style: const TextStyle(fontSize: 8, color: Colors.indigo, fontWeight: FontWeight.bold)),
                    ]),
                  ),
                ),
              );
            }
          ),
      )
    ]);
  }

  Widget _buildEmpty() {
    return Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
      Icon(Icons.layers_clear_outlined, size: 60, color: Colors.grey[300]),
      const SizedBox(height: 16),
      const Text("Belum ada template", style: TextStyle(color: Colors.grey, fontWeight: FontWeight.w500)),
      const Text("Admin belum membuat template di Cloud", style: TextStyle(fontSize: 10, color: Colors.grey)),
    ]));
  }
}
