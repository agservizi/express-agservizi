import '../models/kpi.dart';

class DashboardSnapshot {
  const DashboardSnapshot({
    required this.kpis,
    required this.dailyRevenue,
    required this.topStores,
    required this.topProducts,
    required this.updatedAt,
    required this.isLive,
    required this.statusMessage,
  });

  final List<Kpi> kpis;
  final List<ChartPoint> dailyRevenue;
  final List<RankedValue> topStores;
  final List<RankedValue> topProducts;
  final DateTime updatedAt;
  final bool isLive;
  final String statusMessage;
}

class MockDashboardRepository {
  DashboardSnapshot load({String statusMessage = 'Dati demo attivi.'}) {
    final now = DateTime.now();
    return DashboardSnapshot(
      kpis: const [
        Kpi(label: 'Vendite totali', value: '€ 482.350', delta: '+6,8%'),
        Kpi(label: 'Oggi', value: '€ 18.420', delta: '+4,2%'),
        Kpi(label: 'Ultimi 7 giorni', value: '€ 112.980', delta: '+3,1%'),
        Kpi(label: 'Ultimi 30 giorni', value: '€ 351.740', delta: '+7,4%'),
        Kpi(label: 'Scontrino medio', value: '€ 56,90', delta: '+1,2%'),
        Kpi(label: 'Margine medio', value: '28,4%', delta: '+0,9%'),
      ],
      dailyRevenue: const [
        ChartPoint(0, 12.4),
        ChartPoint(1, 14.1),
        ChartPoint(2, 11.6),
        ChartPoint(3, 16.2),
        ChartPoint(4, 15.1),
        ChartPoint(5, 18.9),
        ChartPoint(6, 19.4),
        ChartPoint(7, 17.2),
        ChartPoint(8, 21.5),
        ChartPoint(9, 22.1),
        ChartPoint(10, 19.8),
        ChartPoint(11, 23.7),
        ChartPoint(12, 21.4),
        ChartPoint(13, 24.6),
      ],
      topStores: const [
        RankedValue(label: 'Milano Duomo', value: 98.2),
        RankedValue(label: 'Roma Centro', value: 84.7),
        RankedValue(label: 'Napoli Vomero', value: 73.9),
        RankedValue(label: 'Torino Porta', value: 65.3),
        RankedValue(label: 'Bologna Fiera', value: 59.8),
      ],
      topProducts: const [
        RankedValue(label: 'eSIM Premium', value: 44.8),
        RankedValue(label: 'Fibra 2.5G', value: 39.1),
        RankedValue(label: 'PhoneCare Plus', value: 35.6),
        RankedValue(label: 'Smart Bundle', value: 31.2),
        RankedValue(label: 'Router Go', value: 28.4),
      ],
      updatedAt: DateTime(now.year, now.month, now.day, now.hour, now.minute),
      isLive: false,
      statusMessage: statusMessage,
    );
  }
}
