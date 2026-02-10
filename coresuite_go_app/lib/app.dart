import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import 'screens/dashboard_screen.dart';

typedef ColorPair = ({Color primary, Color surface});

class CoresuiteGoApp extends StatelessWidget {
  const CoresuiteGoApp({super.key});

  static const ColorPair _brand = (
    primary: Color(0xFF0F766E),
    surface: Color(0xFF0B1220),
  );

  @override
  Widget build(BuildContext context) {
    final baseTextTheme = GoogleFonts.spaceGroteskTextTheme(
      Theme.of(context).textTheme,
    );

    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Coresuite Go',
      theme: ThemeData(
        brightness: Brightness.dark,
        colorScheme: ColorScheme.fromSeed(
          seedColor: _brand.primary,
          brightness: Brightness.dark,
        ).copyWith(surface: _brand.surface, primary: _brand.primary),
        textTheme: baseTextTheme,
        useMaterial3: true,
      ),
      home: const DashboardScreen(),
    );
  }
}
