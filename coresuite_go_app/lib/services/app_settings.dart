import 'package:shared_preferences/shared_preferences.dart';

class AppSettings {
  static const String _apiBaseUrlKey = 'api_base_url';
  static const String _apiTokenKey = 'api_token';
  static const String defaultApiBaseUrl =
      'https://express.agenziaplinio.it/index.php?page=api';

  Future<String> loadApiBaseUrl() async {
    final prefs = await SharedPreferences.getInstance();
    final stored = (prefs.getString(_apiBaseUrlKey) ?? '').trim();
    if (stored.isEmpty) {
      await prefs.setString(_apiBaseUrlKey, defaultApiBaseUrl);
      return defaultApiBaseUrl;
    }
    return stored;
  }

  Future<void> saveApiBaseUrl(String url) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_apiBaseUrlKey, url.trim());
  }

  Future<String> loadApiToken() async {
    final prefs = await SharedPreferences.getInstance();
    return (prefs.getString(_apiTokenKey) ?? '').trim();
  }

  Future<void> saveApiToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_apiTokenKey, token.trim());
  }

  Future<void> clearApiToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_apiTokenKey);
  }
}
