import 'dart:math';

import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';

import '../data/dashboard_repository.dart';
import '../data/mock_dashboard.dart';
import '../models/kpi.dart';
import '../services/api_client.dart';
import '../services/app_settings.dart';
import '../widgets/kpi_card.dart';
import '../widgets/ranked_list.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  final _repository = DashboardRepository();
  final _settings = AppSettings();
  final _fallbackSnapshot = MockDashboardRepository().load();
  int _selectedRange = 0;
  String _apiBaseUrl = '';
  String _apiToken = '';
  late Future<DashboardSnapshot> _snapshotFuture;

  @override
  void initState() {
    super.initState();
    _snapshotFuture = _loadSnapshot();
  }

  Future<DashboardSnapshot> _loadSnapshot() async {
    final baseUrl = _apiBaseUrl.isNotEmpty
        ? _apiBaseUrl
        : await _settings.loadApiBaseUrl();
    final token = _apiToken.isNotEmpty
        ? _apiToken
        : await _settings.loadApiToken();
    if (mounted) {
      setState(() {
        _apiBaseUrl = baseUrl;
        _apiToken = token;
      });
    }
    return _repository.load(
      baseUrl: baseUrl,
      token: token,
      view: _rangeToView(_selectedRange),
    );
  }

  Future<void> _reload() async {
    setState(() => _snapshotFuture = _loadSnapshot());
    await _snapshotFuture;
  }

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;

    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [
              colors.surface,
              const Color(0xFF0E1A2F),
              const Color(0xFF0D1D2A),
            ],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: SafeArea(
          child: FutureBuilder<DashboardSnapshot>(
            future: _snapshotFuture,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return const Center(child: CircularProgressIndicator());
              }

              if (snapshot.hasError) {
                return _buildErrorState(context, snapshot.error);
              }

              final data = snapshot.data ?? _fallbackSnapshot;
              return RefreshIndicator(
                onRefresh: _reload,
                child: CustomScrollView(
                  slivers: [
                    SliverToBoxAdapter(child: _buildHeader(context, data)),
                    SliverPadding(
                      padding: const EdgeInsets.symmetric(horizontal: 20),
                      sliver: SliverToBoxAdapter(
                        child: _buildApiBanner(context, data),
                      ),
                    ),
                    SliverPadding(
                      padding: const EdgeInsets.symmetric(horizontal: 20),
                      sliver: SliverToBoxAdapter(
                        child: _buildRangeSelector(context),
                      ),
                    ),
                    SliverPadding(
                      padding: const EdgeInsets.all(20),
                      sliver: SliverGrid.builder(
                        gridDelegate:
                            const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 2,
                              mainAxisSpacing: 16,
                              crossAxisSpacing: 16,
                              childAspectRatio: 1.1,
                            ),
                        itemCount: data.kpis.length,
                        itemBuilder: (context, index) =>
                            KpiCard(kpi: data.kpis[index]),
                      ),
                    ),
                    SliverPadding(
                      padding: const EdgeInsets.symmetric(horizontal: 20),
                      sliver: SliverToBoxAdapter(
                        child: _buildRevenueChart(context, data.dailyRevenue),
                      ),
                    ),
                    SliverPadding(
                      padding: const EdgeInsets.fromLTRB(20, 20, 20, 32),
                      sliver: SliverToBoxAdapter(
                        child: Column(
                          children: [
                            RankedList(
                              title: 'Top 5 operatori',
                              subtitle: 'Valori in migliaia',
                              values: data.topStores,
                            ),
                            const SizedBox(height: 16),
                            RankedList(
                              title: 'Metodi di pagamento',
                              subtitle: 'Valori in migliaia',
                              values: data.topProducts,
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
        ),
      ),
    );
  }

  Widget _buildHeader(BuildContext context, DashboardSnapshot snapshot) {
    final isLive = snapshot.isLive;
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Coresuite Go',
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Supervisione vendite',
                    style: Theme.of(
                      context,
                    ).textTheme.bodyMedium?.copyWith(color: Colors.white70),
                  ),
                ],
              ),
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 8,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.08),
                      borderRadius: BorderRadius.circular(24),
                    ),
                    child: Row(
                      children: [
                        Icon(
                          Icons.bolt,
                          size: 16,
                          color: isLive ? Colors.amberAccent : Colors.white54,
                        ),
                        const SizedBox(width: 6),
                        Text(isLive ? 'LIVE' : 'DEMO'),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton(
                    onPressed: () => _openSettingsDialog(context),
                    icon: const Icon(Icons.tune, color: Colors.white70),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            'Aggiornato: ${_formatTime(snapshot.updatedAt)}',
            style: Theme.of(
              context,
            ).textTheme.bodySmall?.copyWith(color: Colors.white54),
          ),
        ],
      ),
    );
  }

  Widget _buildApiBanner(BuildContext context, DashboardSnapshot snapshot) {
    final hasLive = snapshot.isLive;
    final baseUrl = _apiBaseUrl.isNotEmpty
        ? _apiBaseUrl
        : AppSettings.defaultApiBaseUrl;
    final background = hasLive
        ? Colors.white.withOpacity(0.08)
        : const Color(0xFF1F2937).withOpacity(0.7);
    final border = hasLive ? Colors.white12 : const Color(0xFF38BDF8);
    final icon = hasLive ? Icons.cloud_done : Icons.cloud_off;
    final label = hasLive ? 'API: $baseUrl' : snapshot.statusMessage;

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: border),
      ),
      child: Row(
        children: [
          Icon(icon, color: Colors.white70, size: 18),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              label,
              style: Theme.of(
                context,
              ).textTheme.bodySmall?.copyWith(color: Colors.white70),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _openSettingsDialog(BuildContext context) async {
    final urlController = TextEditingController(
      text: _apiBaseUrl.isEmpty ? AppSettings.defaultApiBaseUrl : _apiBaseUrl,
    );
    final usernameController = TextEditingController();
    final passwordController = TextEditingController();
    final theme = Theme.of(context);
    await showDialog<void>(
      context: context,
      builder: (context) {
        var isLoading = false;
        var errorMessage = '';
        return StatefulBuilder(
          builder: (context, setDialogState) => AlertDialog(
            backgroundColor: const Color(0xFF0F172A),
            title: const Text('Configurazione API'),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: urlController,
                  decoration: const InputDecoration(
                    labelText: 'Base URL',
                    hintText:
                        'https://express.agenziaplinio.it/index.php?page=api',
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: usernameController,
                  decoration: const InputDecoration(labelText: 'Username'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: passwordController,
                  obscureText: true,
                  decoration: const InputDecoration(labelText: 'Password'),
                ),
                if (errorMessage.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  Text(
                    errorMessage,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: Colors.redAccent,
                    ),
                  ),
                ],
              ],
            ),
            actions: [
              TextButton(
                onPressed: isLoading
                    ? null
                    : () async {
                        final value = urlController.text.trim();
                        await _settings.saveApiBaseUrl(value);
                        if (!mounted) {
                          return;
                        }
                        setState(() => _apiBaseUrl = value);
                        await _reload();
                      },
                child: Text('Salva URL', style: theme.textTheme.labelLarge),
              ),
              TextButton(
                onPressed: isLoading
                    ? null
                    : () async {
                        await _settings.clearApiToken();
                        if (!mounted) {
                          return;
                        }
                        setState(() => _apiToken = '');
                        await _reload();
                      },
                child: Text('Esci', style: theme.textTheme.labelLarge),
              ),
              TextButton(
                onPressed: isLoading ? null : () => Navigator.of(context).pop(),
                child: Text('Chiudi', style: theme.textTheme.labelLarge),
              ),
              ElevatedButton(
                onPressed: isLoading
                    ? null
                    : () async {
                        final baseUrl = urlController.text.trim();
                        final username = usernameController.text.trim();
                        final password = passwordController.text.trim();
                        if (username.isEmpty || password.isEmpty) {
                          setDialogState(
                            () => errorMessage = 'Inserisci le credenziali.',
                          );
                          return;
                        }
                        setDialogState(() {
                          isLoading = true;
                          errorMessage = '';
                        });
                        try {
                          final token = await ApiClient(
                            baseUrl: baseUrl,
                            token: '',
                          ).login(username: username, password: password);
                          await _settings.saveApiBaseUrl(baseUrl);
                          await _settings.saveApiToken(token);
                          if (!mounted) {
                            return;
                          }
                          setState(() {
                            _apiBaseUrl = baseUrl;
                            _apiToken = token;
                          });
                          await _reload();
                          if (context.mounted) {
                            Navigator.of(context).pop();
                          }
                        } catch (error) {
                          setDialogState(() {
                            errorMessage = error.toString();
                            isLoading = false;
                          });
                        }
                      },
                child: isLoading
                    ? const SizedBox(
                        height: 16,
                        width: 16,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Accedi'),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildRangeSelector(BuildContext context) {
    final ranges = ['Giorno', 'Mese', 'Anno'];

    return Row(
      children: List.generate(ranges.length, (index) {
        final isSelected = _selectedRange == index;
        return Expanded(
          child: Padding(
            padding: EdgeInsets.only(right: index == ranges.length - 1 ? 0 : 8),
            child: GestureDetector(
              onTap: () {
                setState(() => _selectedRange = index);
                _reload();
              },
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 220),
                padding: const EdgeInsets.symmetric(vertical: 10),
                decoration: BoxDecoration(
                  color: isSelected
                      ? Colors.white
                      : Colors.white.withOpacity(0.08),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(
                    color: isSelected ? Colors.transparent : Colors.white24,
                  ),
                ),
                child: Text(
                  ranges[index],
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.labelLarge?.copyWith(
                    color: isSelected
                        ? const Color(0xFF0B1220)
                        : Colors.white70,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
          ),
        );
      }),
    );
  }

  Widget _buildRevenueChart(BuildContext context, List<ChartPoint> points) {
    final values = points.map((point) => point.y).toList(growable: false);
    final minValue = values.isEmpty ? 0.0 : values.reduce(min);
    final maxValue = values.isEmpty ? 1.0 : values.reduce(max);
    final minY = values.isEmpty ? 0.0 : max(0.0, minValue * 0.9);
    final maxY = values.isEmpty ? 1.0 : maxValue * 1.1;
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
            'Trend vendite',
            style: Theme.of(
              context,
            ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 4),
          Text(
            'Valori in migliaia',
            style: Theme.of(
              context,
            ).textTheme.bodySmall?.copyWith(color: Colors.white54),
          ),
          const SizedBox(height: 16),
          SizedBox(
            height: 180,
            child: LineChart(
              LineChartData(
                gridData: const FlGridData(show: false),
                borderData: FlBorderData(show: false),
                titlesData: const FlTitlesData(show: false),
                lineBarsData: [
                  LineChartBarData(
                    spots: points
                        .map((point) => FlSpot(point.x, point.y))
                        .toList(growable: false),
                    isCurved: true,
                    color: const Color(0xFF34D399),
                    barWidth: 3,
                    dotData: const FlDotData(show: false),
                    belowBarData: BarAreaData(
                      show: true,
                      gradient: LinearGradient(
                        colors: [
                          const Color(0xFF34D399).withOpacity(0.3),
                          const Color(0xFF34D399).withOpacity(0.0),
                        ],
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                      ),
                    ),
                  ),
                ],
                minY: minY,
                maxY: maxY,
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _rangeToView(int index) {
    if (index == 1) {
      return 'monthly';
    }
    if (index == 2) {
      return 'yearly';
    }
    return 'daily';
  }

  Widget _buildErrorState(BuildContext context, Object? error) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off, size: 32, color: Colors.white70),
            const SizedBox(height: 12),
            Text(
              'Impossibile caricare i dati.',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 6),
            Text(
              error?.toString() ?? 'Errore sconosciuto',
              textAlign: TextAlign.center,
              style: Theme.of(
                context,
              ).textTheme.bodySmall?.copyWith(color: Colors.white70),
            ),
            const SizedBox(height: 16),
            ElevatedButton(onPressed: _reload, child: const Text('Riprova')),
          ],
        ),
      ),
    );
  }

  String _formatTime(DateTime timestamp) {
    final hour = timestamp.hour.toString().padLeft(2, '0');
    final minute = timestamp.minute.toString().padLeft(2, '0');
    return '$hour:$minute';
  }
}
