import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../models/label_data.dart';
import '../../widgets/mini_widgets.dart';

class QueueTab extends StatefulWidget {
  final List<LabelData> filteredQueue;
  final int subTab;
  final List<int> selectedIds;
  final Function(int) onTabChange;
  final Function(String) onSearch;
  final Function(LabelData) onShowDetail;
  final Function(LabelData) onPrint;
  final Function(LabelData) onSync;
  final Function(LabelData) onDelete;
  final Function(List<int>) onDeleteMassal;
  final VoidCallback onCancelSelect;

  const QueueTab({
    super.key, required this.filteredQueue, required this.subTab, required this.selectedIds,
    required this.onTabChange, required this.onSearch, required this.onShowDetail,
    required this.onPrint, required this.onSync, required this.onDelete,
    required this.onDeleteMassal, required this.onCancelSelect
  });

  @override
  State<QueueTab> createState() => _QueueTabState();
}

class _QueueTabState extends State<QueueTab> {
  @override
  Widget build(BuildContext context) {
    bool isRT = widget.subTab == 1;
    
    // FIX: DefaultTabController ensures the TabBar has a controller
    return DefaultTabController(
      length: 2,
      initialIndex: widget.subTab,
      child: Column(children: [
        Container(
          color: Colors.white, 
          child: TabBar(
            onTap: widget.onTabChange, 
            tabs: const [Tab(text: "BELUM DICETAK"), Tab(text: "CETAK ULANG")], 
            labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12), 
            labelColor: Colors.indigo[900], 
            unselectedLabelColor: Colors.grey, 
            indicatorColor: Colors.indigo[900]
          )
        ),
        Padding(
          padding: const EdgeInsets.all(16), 
          child: TextField(
            onChanged: widget.onSearch, 
            decoration: InputDecoration(
              hintText: "Cari data...", 
              prefixIcon: const Icon(Icons.search, size: 20), 
              isDense: true, 
              fillColor: Colors.white, 
              filled: true, 
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none), 
              contentPadding: EdgeInsets.zero
            )
          )
        ),
        if (widget.selectedIds.isNotEmpty && !isRT) 
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10), 
            color: Colors.blue[50], 
            child: Row(children: [
              Text("${widget.selectedIds.length} TERPILIH", style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Colors.blue)), 
              const Spacer(), 
              TextButton(onPressed: widget.onCancelSelect, child: const Text("BATAL PILIH", style: TextStyle(color: Colors.blue, fontWeight: FontWeight.bold, fontSize: 11))), 
              const SizedBox(width: 8), 
              TextButton(onPressed: () => widget.onDeleteMassal(widget.selectedIds), child: const Text("HAPUS SEMUA", style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 11)))
            ])
          ),
        
        // Expanded ensures the ListView takes remaining space and doesn't overflow
        Expanded(
          child: widget.filteredQueue.isEmpty 
            ? Center(child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [Icon(Icons.layers_clear_rounded, size: 60, color: Colors.grey[200]), const SizedBox(height: 16), const Text("Antrean Kosong", style: TextStyle(color: Colors.grey, fontWeight: FontWeight.w500))])) 
            : ListView.builder(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 20), 
                itemCount: widget.filteredQueue.length, 
                itemBuilder: (context, index) {
                  final item = widget.filteredQueue[index]; 
                  bool isL = item.isSynced; 
                  Color color = isRT ? Colors.indigo : (isL ? Colors.blue : Colors.orange); 
                  IconData icon = isRT ? Icons.replay_rounded : (isL ? Icons.lock_rounded : Icons.edit_note_rounded);
                  bool isSelected = widget.selectedIds.contains(item.id);
                  
                  return Container(
                    margin: const EdgeInsets.only(bottom: 12), 
                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(15), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 10, offset: const Offset(0, 2))], border: Border.all(color: isSelected ? Colors.blue : color.withOpacity(0.1), width: 1)), 
                    child: InkWell(
                      borderRadius: BorderRadius.circular(15), 
                      onLongPress: () { if(!isRT && !isL) { HapticFeedback.mediumImpact(); setState(() { if(!widget.selectedIds.contains(item.id)) widget.selectedIds.add(item.id!); }); } }, 
                      onTap: () { if(widget.selectedIds.isNotEmpty && !isRT && !isL) { setState(() { if(widget.selectedIds.contains(item.id)) widget.selectedIds.remove(item.id); else widget.selectedIds.add(item.id!); }); } else { widget.onShowDetail(item); } }, 
                      child: Padding(padding: const EdgeInsets.all(16), child: Column(children: [
                        Row(crossAxisAlignment: CrossAxisAlignment.center, children: [
                          Container(width: 50, height: 50, decoration: BoxDecoration(color: isSelected ? Colors.blue.withOpacity(0.2) : color.withOpacity(0.08), borderRadius: BorderRadius.circular(15), border: isSelected ? Border.all(color: Colors.blue, width: 2) : null), child: (widget.selectedIds.isNotEmpty && !isRT && !isL) ? Checkbox(value: isSelected, activeColor: Colors.blue, checkColor: Colors.white, side: BorderSide.none, onChanged: (v) => setState(() { if(v!) widget.selectedIds.add(item.id!); else widget.selectedIds.remove(item.id!); })) : Icon(icon, color: color, size: 26)),
                          const SizedBox(width: 16),
                          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [Expanded(child: Text(item.batch, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 10, color: Colors.grey, letterSpacing: 0.5))), Text(item.time, style: const TextStyle(fontSize: 10, color: Colors.grey, fontWeight: FontWeight.bold))]), const SizedBox(height: 2), Text("${item.item} • ${item.size}${item.unit}", style: const TextStyle(fontSize: 13, color: Colors.black87, fontWeight: FontWeight.w900)), const SizedBox(height: 8), MiniBadge(text: item.machine)]))
                        ]),
                        const SizedBox(height: 16), const Divider(height: 1), const SizedBox(height: 12),
                        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                          if (!isRT) Container(padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2), decoration: BoxDecoration(color: Colors.grey[100], borderRadius: BorderRadius.circular(8)), child: Row(children: [
                            _btn(Icons.remove, () { if(item.deltaCount > 1) setState(() => item.deltaCount--); }),
                            SizedBox(width: 32, child: TextFormField(key: ValueKey("q_${item.id}_${item.deltaCount}"), initialValue: item.deltaCount.toString(), textAlign: TextAlign.center, keyboardType: TextInputType.number, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 13, color: Colors.indigo), decoration: const InputDecoration(isDense: true, border: InputBorder.none, contentPadding: EdgeInsets.zero), onChanged: (v) => item.deltaCount = int.tryParse(v) ?? 1)),
                            _btn(Icons.add, () => setState(() => item.deltaCount++)),
                          ])) else Text("TOTAL: ${item.copies} LABEL", style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w900, color: Colors.indigo)),
                          Row(children: [
                            if (!isRT && !isL) IconButton(icon: const Icon(Icons.cloud_upload_outlined, color: Colors.blue, size: 18), onPressed: () => widget.onSync(item), padding: const EdgeInsets.all(8), constraints: const BoxConstraints()),
                            if (!isRT && !isL) IconButton(icon: const Icon(Icons.delete_outline_rounded, color: Colors.red, size: 18), onPressed: () => widget.onDelete(item), padding: const EdgeInsets.all(8), constraints: const BoxConstraints()),
                            const SizedBox(width: 4),
                            ElevatedButton.icon(onPressed: () => widget.onPrint(item), icon: Icon(isRT ? Icons.settings_suggest_rounded : Icons.print_rounded, size: 14), label: Text(isRT ? "OPSI" : "PRINT", style: const TextStyle(fontSize: 11)), style: ElevatedButton.styleFrom(backgroundColor: isRT ? Colors.indigo[900] : Colors.blue[700], foregroundColor: Colors.white, elevation: 0, minimumSize: const Size(0, 32), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8))))
                          ])
                        ])
                      ])),
                    ),
                  );
                }
              ),
        )
      ]),
    );
  }

  Widget _btn(IconData icon, VoidCallback onPressed) => InkWell(onTap: onPressed, borderRadius: BorderRadius.circular(4), child: Padding(padding: const EdgeInsets.all(4), child: Icon(icon, size: 18, color: Colors.indigo)));
}
