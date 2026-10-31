import 'package:dio/dio.dart';
import 'package:familytrack/core/network/api_client.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

class FcmService {
  static final FcmService _instance = FcmService._internal();
  factory FcmService() => _instance;
  FcmService._internal();

  final FirebaseMessaging _messaging = FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _localNotifications = FlutterLocalNotificationsPlugin();

  Future<void> initialize() async {
    // 1. Request notification permissions
    final settings = await _messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    if (settings.authorizationStatus == AuthorizationStatus.authorized ||
        settings.authorizationStatus == AuthorizationStatus.provisional) {
      debugPrint('FCM: Notification permissions granted');
    }

    // 2. Initialize local notifications for foreground push presentation
    const androidInit = AndroidInitializationSettings('@mipmap/ic_launcher');
    const initSettings = InitializationSettings(android: androidInit);
    await _localNotifications.initialize(settings: initSettings);

    // 3. Listen to foreground messages
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      final notification = message.notification;
      if (notification != null) {
        _localNotifications.show(
          id: notification.hashCode,
          title: notification.title,
          body: notification.body,
          notificationDetails: const NotificationDetails(
            android: AndroidNotificationDetails(
              'familytrack_alerts',
              'Safety & Alerts',
              importance: Importance.high,
              priority: Priority.high,
              icon: '@mipmap/ic_launcher',
            ),
          ),
        );
      }
    });

    // 4. Token refresh listener
    // ignore: deprecated_member_use
    _messaging.onTokenRefresh.listen((newToken) {
      registerTokenWithBackend(newToken);
    });
  }

  /// Sends the current FCM device token to the Laravel backend
  Future<void> registerCurrentToken() async {
    try {
      // ignore: deprecated_member_use
      final token = await _messaging.getToken();
      if (token != null) {
        await registerTokenWithBackend(token);
      }
    } catch (e) {
      debugPrint('FCM: Error getting device token: $e');
    }
  }

  Future<void> registerTokenWithBackend(String token) async {
    try {
      final response = await ApiClient().dio.put(
        '/me/fcm-token',
        data: {
          'token': token,
          'platform': 'android',
        },
      );
      debugPrint('FCM: Token successfully registered with backend: ${response.statusCode}');
    } on DioException catch (e) {
      debugPrint('FCM: Failed to register token with backend: ${e.response?.data ?? e.message}');
    } catch (e) {
      debugPrint('FCM: Unexpected error during token sync: $e');
    }
  }
}
