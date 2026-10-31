import 'dart:io';

class AppConfig {
  /// Default base URL for Laravel API.
  /// Android Emulator communicates with host machine on 10.0.2.2:8000.
  /// When testing on a physical phone, pass `--dart-define=BACKEND_BASE_URL=http://YOUR_LAN_IP:8000/api`
  static String get baseUrl {
    const fromEnv = String.fromEnvironment('BACKEND_BASE_URL');
    if (fromEnv.isNotEmpty) return fromEnv;

    if (Platform.isAndroid) {
      return 'http://10.0.2.2:8000/api';
    }
    return 'http://127.0.0.1:8000/api';
  }
}
