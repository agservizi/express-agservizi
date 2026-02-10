import 'package:flutter/material.dart';

import '../models/kpi.dart';

class RankedList extends StatelessWidget {
  const RankedList({
    super.key,
    required this.title,
    required this.subtitle,
    required this.values,
  });

  final String title;
  final String subtitle;
  final List<RankedValue> values;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.06),
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Colors.white12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: Theme.of(
              context,
            ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 4),
          Text(
            subtitle,
            style: Theme.of(
              context,
            ).textTheme.bodySmall?.copyWith(color: Colors.white54),
          ),
          const SizedBox(height: 16),
          ...values.map((value) => _RankedRow(value: value)),
        ],
      ),
    );
  }
}

class _RankedRow extends StatelessWidget {
  const _RankedRow({required this.value});

  final RankedValue value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Expanded(
            child: Text(
              value.label,
              style: Theme.of(context).textTheme.bodyMedium,
            ),
          ),
          Text(
            '${value.value.toStringAsFixed(1)}',
            style: Theme.of(
              context,
            ).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
          ),
          const SizedBox(width: 8),
          const Icon(Icons.trending_up, size: 16, color: Color(0xFF34D399)),
        ],
      ),
    );
  }
}
