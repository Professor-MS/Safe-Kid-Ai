import 'package:dio/dio.dart';
import 'package:familytrack/core/config/app_config.dart';
import 'package:firebase_auth/firebase_auth.dart';

class ApiClient {
  static final ApiClient _instance = ApiClient._internal();
  factory ApiClient() => _instance;

  late final Dio dio;

  ApiClient._internal() {
    dio = Dio(
      BaseOptions(
        baseUrl: AppConfig.baseUrl,
        connectTimeout: const Duration(seconds: 10),
        receiveTimeout: const Duration(seconds: 10),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      ),
    );

    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          // Token Injection: Fetch valid Firebase ID token directly from FirebaseAuth
          try {
            final user = FirebaseAuth.instance.currentUser;
            if (user != null) {
              final token = await user.getIdToken();
              if (token != null) {
                options.headers['Authorization'] = 'Bearer $token';
              }
            }
          } catch (_) {
            // If token fetch fails, proceed with request (will receive 401)
          }
          return handler.next(options);
        },
        onError: (DioException err, handler) async {
          // Requirement: On 401, force-refresh Firebase ID token and retry once
          final is401 = err.response?.statusCode == 401;
          final alreadyRetried = err.requestOptions.extra['retried'] == true;

          if (is401 && !alreadyRetried) {
            try {
              err.requestOptions.extra['retried'] = true;
              final user = FirebaseAuth.instance.currentUser;
              if (user != null) {
                // Force token refresh from Firebase
                final refreshedToken = await user.getIdToken(true);
                if (refreshedToken != null) {
                  err.requestOptions.headers['Authorization'] = 'Bearer $refreshedToken';
                  final retryResponse = await dio.fetch(err.requestOptions);
                  return handler.resolve(retryResponse);
                }
              }
            } catch (retryError) {
              // If retry fails, pass through original error
            }
          }
          return handler.next(err);
        },
      ),
    );
  }
}

