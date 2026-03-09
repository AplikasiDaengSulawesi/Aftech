import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../widgets/mini_widgets.dart';

class InputTab extends StatelessWidget {
  final bool isWide;
  final String selectedItem;
  final String selectedUnit;
  final String selectedMachine;
  final String selectedShift;
  final List<String> items;
  final List<String> units;
  final List<String> machines;
  final List<String> shifts;
  final List<String> availableSizes;
  final List<String> availableQuantities;
  final List<Map<String, dynamic>> machineDetails;
  final TextEditingController sizeController;
  final TextEditingController qtyController;
  final TextEditingController labelCountController;
  final TextEditingController opController;
  final TextEditingController qcController;
  final TextEditingController batchController;
  final String currentTime;
  final DateTime selectedDate;
  
  final Function(String?) onItemChanged;
  final Function(String?) onUnitChanged;
  final Function(String?) onMachineChanged;
  final Function(String?) onShiftChanged;
  final VoidCallback onSave;
  final VoidCallback onGenerateBatch;

  const InputTab({
    super.key, required this.isWide, required this.selectedItem, required this.selectedUnit,
    required this.selectedMachine, required this.selectedShift, required this.items,
    required this.units, required this.machines, required this.shifts,
    required this.availableSizes, required this.availableQuantities,
    required this.machineDetails, required this.sizeController, required this.qtyController,
    required this.labelCountController,
    required this.opController, required this.qcController, required this.batchController,
    required this.currentTime, required this.selectedDate,
    required this.onItemChanged, required this.onUnitChanged, required this.onMachineChanged,
    required this.onShiftChanged, required this.onSave, required this.onGenerateBatch
  });

  @override
  Widget build(BuildContext context) {
    return Column(children: [
      _batchHeader(),
      
      Expanded(child: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
        child: Column(children: [
          CardGroup(title: "PRODUK & DIMENSI", icon: Icons.inventory_2_rounded, child: Column(children: [
            _dropdown("Jenis Item", selectedItem, items, onItemChanged),
            const SizedBox(height: 16),
            _chips(availableSizes, sizeController, "Pilih Ukuran Cepat:"),
            Row(children: [
              Expanded(flex: 2, child: _field(sizeController, "Ukuran Manual", Icons.straighten_rounded)),
              const SizedBox(width: 12),
              Expanded(child: _dropdown("Unit", selectedUnit, units, onUnitChanged))
            ])
          ])),
          CardGroup(title: "KONFIGURASI PRODUKSI", icon: Icons.settings_suggest_rounded, child: Column(children: [
            Row(children: [
              Expanded(child: _machineDropdown()),
              const SizedBox(width: 12),
              Expanded(child: _dropdown("Shift", selectedShift, shifts, onShiftChanged))
            ]),
            const SizedBox(height: 16),
            _chips(availableQuantities, qtyController, "Pilih Qty Cepat:"),
            Row(children: [
              Expanded(flex: 2, child: _field(qtyController, "Isi per Kemasan", Icons.pin_outlined)),
              const SizedBox(width: 12),
              Expanded(child: _field(labelCountController, "Jumlah Lembar", Icons.copy_all_rounded)),
            ])
          ])),
          CardGroup(title: "OTORISASI", icon: Icons.admin_panel_settings_rounded, child: Row(children: [
            Expanded(child: _field(opController, "Operator", Icons.person_outline_rounded)),
            const SizedBox(width: 12),
            Expanded(child: _field(qcController, "QC Check", Icons.verified_user_outlined))
          ])),
          const SizedBox(height: 20),
        ]),
      )),

      // TOMBOL SIMPAN SEKARANG PAS DI ATAS BATAS NAVBAR
      Container(
        padding: const EdgeInsets.fromLTRB(20, 10, 20, 15),
        decoration: BoxDecoration(
          color: const Color.fromARGB(255, 255, 255, 255)!,
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, -5))]
        ),
        child: ElevatedButton.icon(
          onPressed: onSave, 
          icon: const Icon(Icons.add_task_rounded), 
          label: const Text("SIMPAN KE ANTREAN", style: TextStyle(fontWeight: FontWeight.w900, letterSpacing: 1)), 
          style: ElevatedButton.styleFrom(backgroundColor: Colors.indigo[900], foregroundColor: Colors.white, minimumSize: const Size(double.infinity, 55), elevation: 0, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)))
        ),
      ),
    ]);
  }

  Widget _batchHeader() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(20, 15, 20, 20),
      color: Colors.indigo[900],
      child: Row(children: [
        const Icon(Icons.qr_code_scanner_rounded, color: Colors.white, size: 28),
        const SizedBox(width: 10),
        Expanded(child: FittedBox(fit: BoxFit.scaleDown, alignment: Alignment.centerLeft, child: Text(batchController.text, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 24, letterSpacing: 1.5))))
      ]),
    );
  }

  Widget _field(TextEditingController c, String l, IconData i) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(l, style: const TextStyle(fontSize: 9, color: Colors.grey, fontWeight: FontWeight.bold)),
      const SizedBox(height: 6),
      SizedBox(height: 45, child: TextFormField(controller: c, onChanged: (v)=>onGenerateBatch(), style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold), decoration: InputDecoration(prefixIcon: Icon(i, size: 18, color: Colors.indigo[300]), border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey[200]!)), enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey[200]!)), filled: true, fillColor: Colors.grey[50], contentPadding: const EdgeInsets.symmetric(horizontal: 12))))
    ]);
  }

  Widget _dropdown(String l, String v, List<String> items, Function(String?) onChanged) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(l, style: const TextStyle(fontSize: 9, color: Colors.grey, fontWeight: FontWeight.bold)),
      const SizedBox(height: 6),
      Container(height: 45, padding: const EdgeInsets.symmetric(horizontal: 12), decoration: BoxDecoration(color: Colors.grey[50], borderRadius: BorderRadius.circular(12), border: Border.all(color: Colors.grey[200]!)), child: DropdownButtonHideUnderline(child: DropdownButton<String>(value: v.isEmpty ? (items.isNotEmpty ? items.first : null) : v, isExpanded: true, icon: const Icon(Icons.keyboard_arrow_down_rounded, size: 18), items: items.map((e) => DropdownMenuItem(value: e, child: Text(e, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold)))).toList(), onChanged: (val) { onChanged(val); onGenerateBatch(); })))
    ]);
  }

  Widget _machineDropdown() {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      const Text("Machine", style: TextStyle(fontSize: 9, color: Colors.grey, fontWeight: FontWeight.bold)),
      const SizedBox(height: 6),
      Container(height: 45, padding: const EdgeInsets.symmetric(horizontal: 12), decoration: BoxDecoration(color: Colors.grey[50], borderRadius: BorderRadius.circular(12), border: Border.all(color: Colors.grey[200]!)), child: DropdownButtonHideUnderline(child: DropdownButton<String>(value: selectedMachine, isExpanded: true, icon: const Icon(Icons.keyboard_arrow_down_rounded, size: 18), items: machineDetails.map((m) {
        bool isMaint = m['status'] == 'maintenance';
        return DropdownMenuItem(value: m['name'] as String, child: FittedBox(fit: BoxFit.scaleDown, child: Row(children: [Text(m['name'], style: TextStyle(fontSize: 13, color: isMaint ? Colors.red : Colors.black, fontWeight: FontWeight.bold)), if (isMaint) const Padding(padding: EdgeInsets.only(left: 4), child: Icon(Icons.warning_amber_rounded, size: 12, color: Colors.red))])));
      }).toList(), onChanged: (val) { onMachineChanged(val); onGenerateBatch(); })))
    ]);
  }

  Widget _chips(List<String> opts, TextEditingController c, String l) {
    if (opts.isEmpty) return const SizedBox.shrink();
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(l, style: const TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Colors.grey)),
      const SizedBox(height: 8),
      SizedBox(height: 38, child: ListView.builder(scrollDirection: Axis.horizontal, itemCount: opts.length, itemBuilder: (ctx, i) {
        final v = opts[i]; bool isS = c.text == v;
        return Padding(padding: const EdgeInsets.only(right: 10), child: ChoiceChip(label: Text(v, style: TextStyle(fontSize: 11, fontWeight: isS ? FontWeight.bold : FontWeight.normal, color: isS ? Colors.white : Colors.indigo[900])), selected: isS, selectedColor: Colors.indigo[900], backgroundColor: Colors.white, checkmarkColor: Colors.white, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10), side: BorderSide(color: isS ? Colors.indigo[900]! : Colors.indigo.withOpacity(0.1), width: 1.5)), onSelected: (s) { if(s) { c.text = v; onGenerateBatch(); } }));
      })),
      const SizedBox(height: 12)
    ]);
  }
}
