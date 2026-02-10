import '../models/kpi.dart';
import '../services/api_client.dart';
import 'mock_dashboard.dart';

class DashboardRepository {
  Future<DashboardSnapshot> load({
    required String baseUrl,
    required String token,
    required String view,
  }) async {
    if (baseUrl.trim().isEmpty || token.trim().isEmpty) {
      return MockDashboardRepository().load(
        statusMessage: 'Token assente. Dati demo attivi.',
      );
    }

    try {
      final client = ApiClient(baseUrl: baseUrl, token: token);
      final report = await client.fetchReport(view: view);
      return _fromReport(report);
    } on ApiException catch (error) {
      return MockDashboardRepository().load(
        statusMessage: 'API non disponibile: ${error.message}',
      );
    } catch (_) {
      return MockDashboardRepository().load(
        statusMessage: 'API non disponibile. Dati demo attivi.',
      );
    }
  }

  DashboardSnapshot _fromReport(Map<String, dynamic> report) {
    final totals = _asMap(report['totals']);
    final comparison = _asMap(report['comparison']);
    final deltas = _asMap(comparison['deltas']);
    final trend = _asMap(report['trend']);
    final trendPoints = _asList(trend['points']);
    final operators = _asList(report['operators']);
    final payments = _asList(report['payments']);

    final gross = _asDouble(totals['gross_revenue']);
    final net = _asDouble(totals['net_revenue']);
    final salesCount = _asInt(totals['sales_count']);
    final avgTicket = _asDouble(totals['average_ticket']);
    final avgTicketNet = _asDouble(totals['average_ticket_net']);
    final discountTotal = _asDouble(totals['discount_total']);

    final kpis = [
      Kpi(
        label: 'Vendite totali',
        value: _formatCurrency(gross),
        delta: _formatDelta(deltas['gross_revenue']),
      ),
      Kpi(
        label: 'Scontrini',
        value: _formatNumber(salesCount),
        delta: _formatDelta(deltas['sales_count']),
      ),
      Kpi(
        label: 'Ricavo netto',
        value: _formatCurrency(net),
        delta: _formatDelta(deltas['net_revenue']),
      ),
      Kpi(
        label: 'Scontrino medio',
        value: _formatCurrency(avgTicket),
        delta: _formatDelta(deltas['average_ticket']),
      ),
      Kpi(
        label: 'Scontrino netto',
        value: _formatCurrency(avgTicketNet),
        delta: _formatDelta(deltas['average_ticket_net']),
      ),
      Kpi(
        label: 'Sconti totali',
        value: _formatCurrency(discountTotal),
        delta: 'n/d',
      ),
    ];

    final chartPoints = <ChartPoint>[];
    for (var i = 0; i < trendPoints.length; i++) {
      final point = _asMap(trendPoints[i]);
      final grossPoint = _asDouble(point['gross_revenue']);
      chartPoints.add(ChartPoint(i.toDouble(), grossPoint / 1000));
    }

    final topOperators = operators
        .take(5)
        .map((item) {
          final row = _asMap(item);
          final label = (row['operator_name'] ?? 'Operatore').toString();
          final value = _asDouble(row['net_revenue']) / 1000;
          return RankedValue(label: label, value: value);
        })
        .toList(growable: false);

    final topPayments = payments
        .take(5)
        .map((item) {
          final row = _asMap(item);
          final label = (row['method'] ?? 'Metodo').toString();
          final value = _asDouble(row['net_revenue']) / 1000;
          return RankedValue(label: label, value: value);
        })
        .toList(growable: false);

    return DashboardSnapshot(
      kpis: kpis,
      dailyRevenue: chartPoints,
      topStores: topOperators,
      topProducts: topPayments,
      updatedAt: DateTime.now(),
      isLive: true,
      statusMessage: 'API connessa',
    );
  }

  Map<String, dynamic> _asMap(dynamic value) {
    if (value is Map<String, dynamic>) {
      return value;
    }
    if (value is Map) {
      return Map<String, dynamic>.from(value);
    }
    return const {};
  }

  List<dynamic> _asList(dynamic value) {
    if (value is List) {
      return value;
    }
    return const [];
  }

  double _asDouble(dynamic value) {
    if (value is num) {
      return value.toDouble();
    }
    return double.tryParse(value?.toString() ?? '') ?? 0.0;
  }

  int _asInt(dynamic value) {
    if (value is int) {
      return value;
    }
    if (value is num) {
      return value.toInt();
    }
    return int.tryParse(value?.toString() ?? '') ?? 0;
  }

  String _formatCurrency(double value) {
    final sign = value < 0 ? '-' : '';
    final formatted = _formatNumber(value.abs(), decimals: 2);
    return '€ $sign$formatted';
  }

  String _formatNumber(num value, {int decimals = 0}) {
    final fixed = value.toStringAsFixed(decimals);
    final parts = fixed.split('.');
    final whole = parts[0];
    final buffer = StringBuffer();
    for (var i = 0; i < whole.length; i++) {
      final index = whole.length - i;
      buffer.write(whole[i]);
      if (index > 1 && index % 3 == 1) {
        buffer.write('.');
      }
    }
    if (parts.length == 1 || decimals == 0) {
      return buffer.toString();
    }
    return '${buffer.toString()},${parts[1]}';
  }

  String _formatDelta(dynamic delta) {
    if (delta is Map) {
      final percent = delta['percent'];
      if (percent == null) {
        return 'n/d';
      }
      final value = _asDouble(percent);
      final sign = value >= 0 ? '+' : '';
      final formatted = _formatNumber(value, decimals: 1);
      return '$sign$formatted%';
    }
    return 'n/d';
  }
}
