class Kpi {
  const Kpi({required this.label, required this.value, required this.delta});

  final String label;
  final String value;
  final String delta;
}

class ChartPoint {
  const ChartPoint(this.x, this.y);

  final double x;
  final double y;
}

class RankedValue {
  const RankedValue({required this.label, required this.value});

  final String label;
  final double value;
}
