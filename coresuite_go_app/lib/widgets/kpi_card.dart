import 'package:flutter/material.dart';

import '../models/kpi.dart';

class KpiCard extends StatelessWidget {
  const KpiCard({super.key, required this.kpi});

  final Kpi kpi;

  Color _deltaColor(BuildContext context, String delta) {
    final value = delta.trim();
    if (value.startsWith('-')) {
      return const Color(0xFFF87171);
    }
    if (value.startsWith('+')) {
      return const Color(0xFF34D399);
    }
    return Colors.white54;
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.06),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            kpi.label,
            style: Theme.of(
              context,
            ).textTheme.labelLarge?.copyWith(color: Colors.white70),
          ),
          const Spacer(),
          Text(
            kpi.value,
            style: Theme.of(
              context,
            ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 6),
          Text(
            kpi.delta,
            style: Theme.of(context).textTheme.labelMedium?.copyWith(
              color: _deltaColor(context, kpi.delta),
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}
