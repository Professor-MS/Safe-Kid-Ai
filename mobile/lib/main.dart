import 'package:familytrack/core/router/app_router.dart';
import 'package:familytrack/core/services/fcm_service.dart';
import 'package:familytrack/core/theme/app_theme.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize Firebase native platform bindings (reads google-services.json on Android)
  try {
    await Firebase.initializeApp();
    // Initialize push notifications & foreground presentation
    await FcmService().initialize();
  } catch (e) {
    debugPrint('Firebase initialization notice: $e');
  }

  runApp(
    const ProviderScope(
      child: FamilyTrackApp(),
    ),
  );
}

class FamilyTrackApp extends ConsumerWidget {
  const FamilyTrackApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);

    return MaterialApp.router(
      title: 'FamilyTrack',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      routerConfig: router,
    );
  }
}
