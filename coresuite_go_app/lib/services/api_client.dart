import 'dart:convert';

import 'package:http/http.dart' as http;

class ApiClient {
  ApiClient({required this.baseUrl, required this.token});

  final String baseUrl;
  final String token;

  Uri _buildUri(String path, [Map<String, String>? query]) {
    final cleaned = baseUrl.trim().replaceAll(RegExp(r'/*$'), '');
    final uri = Uri.parse('$cleaned/$path');
    if (query == null || query.isEmpty) {
      return uri;
    }
    return uri.replace(queryParameters: {...uri.queryParameters, ...query});
  }

  Map<String, String> _headers() {
    final headers = <String, String>{'Content-Type': 'application/json'};
    if (token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    return headers;
  }

  Future<String> login({
    required String username,
    required String password,
  }) async {
    final uri = _buildUri('auth', {'action': 'login'});
    final response = await http.post(
      uri,
      headers: _headers(),
      body: jsonEncode({'username': username, 'password': password}),
    );
    if (response.statusCode != 200) {
      throw ApiException('Login non riuscito (${response.statusCode}).');
    }
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (data['success'] != true) {
      final errors = data['errors'] is List ? data['errors'] as List : const [];
      throw ApiException(
        errors.isNotEmpty ? errors.first.toString() : 'Login non riuscito.',
      );
    }
    final token = (data['token'] ?? '').toString();
    if (token.isEmpty) {
      throw ApiException('Token mancante nella risposta.');
    }
    return token;
  }

  Future<Map<String, dynamic>> fetchReport({required String view}) async {
    final uri = _buildUri('reports', {'view': view});
    final response = await http.get(uri, headers: _headers());
    if (response.statusCode != 200) {
      throw ApiException('Report non disponibile (${response.statusCode}).');
    }
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (data['success'] == true && data['data'] is Map) {
      return Map<String, dynamic>.from(data['data'] as Map);
    }
    if (data['data'] is Map) {
      return Map<String, dynamic>.from(data['data'] as Map);
    }
    return data;
  }
}

class ApiException implements Exception {
  ApiException(this.message);

  final String message;

  @override
  String toString() => message;
}
