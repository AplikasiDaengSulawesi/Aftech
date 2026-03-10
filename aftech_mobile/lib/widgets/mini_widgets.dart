import 'package:flutter/material.dart';

class ConfirmDialog extends StatelessWidget {
  final String title;
  final String message;
  final IconData icon;
  final Color color;
  final String confirmText;
  final Widget? contentWidget;

  const ConfirmDialog({
    super.key, required this.title, required this.message,
    required this.icon, required this.color,
    this.confirmText = "YA, LANJUTKAN", this.contentWidget
  });

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(25)),
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: color.withOpacity(0.1), shape: BoxShape.circle),
              child: Icon(icon, color: color, size: 32),
            ),
            const SizedBox(height: 20),
            Text(title, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 18, letterSpacing: 0.5)),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center, style: TextStyle(color: Colors.grey[600], fontSize: 13, height: 1.5)),
            if (contentWidget != null) ...[const SizedBox(height: 16), contentWidget!],
            const SizedBox(height: 30),
            Row(children: [
              Expanded(child: TextButton(onPressed: () => Navigator.pop(context, false), child: Text("BATAL", style: TextStyle(color: Colors.grey[400], fontWeight: FontWeight.bold)))),
              const SizedBox(width: 12),
              Expanded(flex: 2, child: ElevatedButton(
                onPressed: () => Navigator.pop(context, true),
                style: ElevatedButton.styleFrom(backgroundColor: color, foregroundColor: Colors.white, elevation: 0, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)), padding: const EdgeInsets.symmetric(vertical: 14)),
                child: Text(confirmText, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
              )),
            ])
          ],
        ),
      ),
    );
  }
}

class MiniBadge extends StatelessWidget {
  final String text;
  const MiniBadge({super.key, required this.text});
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(color: Colors.grey[50], borderRadius: BorderRadius.circular(4), border: Border.all(color: Colors.grey[200]!)),
      child: Text(text.toUpperCase(), style: const TextStyle(fontSize: 8, color: Colors.grey, fontWeight: FontWeight.bold)),
    );
  }
}

class SmallBadge extends StatelessWidget {
  final IconData icon;
  final String text;
  const SmallBadge({super.key, required this.icon, required this.text});
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(color: Colors.grey[50], borderRadius: BorderRadius.circular(6)),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        Icon(icon, size: 10, color: Colors.grey[400]),
        const SizedBox(width: 4),
        Text(text.toUpperCase(), style: const TextStyle(fontSize: 9, color: Colors.grey, fontWeight: FontWeight.bold)),
      ]),
    );
  }
}

class SimpleStat extends StatelessWidget {
  final String value;
  final String label;
  const SimpleStat({super.key, required this.value, required this.label});
  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        Text(value, style: TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: Colors.indigo[900], height: 1)),
        Text(label, style: const TextStyle(fontSize: 8, fontWeight: FontWeight.bold, color: Colors.grey, letterSpacing: 1)),
      ],
    );
  }
}

class CardGroup extends StatelessWidget {
  final String title;
  final IconData icon;
  final Widget child;
  final Widget? trailing;
  const CardGroup({super.key, required this.title, required this.icon, required this.child, this.trailing});
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      margin: const EdgeInsets.only(bottom: 20),
      decoration: BoxDecoration(
        color: Colors.white, borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.indigo.withOpacity(0.05), width: 1.5),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.01), blurRadius: 10, offset: const Offset(0, 4))]
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Container(padding: const EdgeInsets.all(6), decoration: BoxDecoration(color: Colors.indigo.withOpacity(0.1), borderRadius: BorderRadius.circular(8)), child: Icon(icon, size: 18, color: Colors.indigo)),
          const SizedBox(width: 12),
          Text(title, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w900, color: Colors.indigo, letterSpacing: 0.5)),
          const Spacer(),
          if (trailing != null) trailing!,
        ]),
        const SizedBox(height: 20),
        child
      ]),
    );
  }
}
