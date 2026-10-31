import 'package:dio/dio.dart';
import 'package:familytrack/core/network/api_client.dart';
import 'package:familytrack/core/services/fcm_service.dart';
import 'package:familytrack/features/auth/models/user_profile.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

enum AuthStatus {
  initial,
  authenticated,
  needsRoleSelection,
  unauthenticated,
}

class AuthState {
  final AuthStatus status;
  final UserProfile? user;
  final User? firebaseUser;
  final bool isLoading;
  final String? errorMessage;

  const AuthState({
    required this.status,
    this.user,
    this.firebaseUser,
    this.isLoading = false,
    this.errorMessage,
  });

  factory AuthState.initial() => const AuthState(status: AuthStatus.initial);

  AuthState copyWith({
    AuthStatus? status,
    UserProfile? user,
    User? firebaseUser,
    bool? isLoading,
    String? errorMessage,
  }) {
    return AuthState(
      status: status ?? this.status,
      user: user ?? this.user,
      firebaseUser: firebaseUser ?? this.firebaseUser,
      isLoading: isLoading ?? this.isLoading,
      errorMessage: errorMessage,
    );
  }
}

class AuthNotifier extends Notifier<AuthState> {
  @override
  AuthState build() {
    // Listen to Firebase Auth state changes
    FirebaseAuth.instance.authStateChanges().listen((fbUser) {
      if (fbUser == null) {
        state = state.copyWith(
          status: AuthStatus.unauthenticated,
          user: null,
          firebaseUser: null,
          isLoading: false,
        );
      } else {
        // User logged into Firebase; sync with Laravel backend
        syncProfileWithBackend(fbUser);
      }
    });

    return AuthState.initial();
  }

  Future<void> syncProfileWithBackend(User fbUser) async {
    state = state.copyWith(isLoading: true, firebaseUser: fbUser, errorMessage: null);

    try {
      final response = await ApiClient().dio.get('/me');
      if (response.statusCode == 200 && response.data['data'] != null) {
        final profile = UserProfile.fromJson(response.data['data']);
        state = state.copyWith(
          status: AuthStatus.authenticated,
          user: profile,
          isLoading: false,
        );
        // Register FCM device token on successful login
        FcmService().registerCurrentToken();
        return;
      }
    } on DioException catch (e) {
      if (e.response?.statusCode == 404) {
        // User is authenticated in Firebase but profile has not been chosen in backend
        state = state.copyWith(
          status: AuthStatus.needsRoleSelection,
          isLoading: false,
        );
        return;
      }
      state = state.copyWith(
        status: AuthStatus.unauthenticated,
        isLoading: false,
        errorMessage: e.response?.data?['message'] ?? e.message,
      );
    } catch (e) {
      state = state.copyWith(
        status: AuthStatus.unauthenticated,
        isLoading: false,
        errorMessage: e.toString(),
      );
    }
  }

  Future<bool> signIn(String email, String password) async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final userCredential = await FirebaseAuth.instance.signInWithEmailAndPassword(
        email: email.trim(),
        password: password,
      );
      if (userCredential.user != null) {
        await syncProfileWithBackend(userCredential.user!);
        return true;
      }
      return false;
    } on FirebaseAuthException catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.message ?? 'Authentication failed',
      );
      return false;
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString(),
      );
      return false;
    }
  }

  Future<bool> signUp({
    required String email,
    required String password,
    required String name,
    required String role,
  }) async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final userCredential = await FirebaseAuth.instance.createUserWithEmailAndPassword(
        email: email.trim(),
        password: password,
      );

      final fbUser = userCredential.user;
      if (fbUser == null) throw Exception('Firebase user creation failed');

      // Update Firebase display name
      await fbUser.updateDisplayName(name);

      // Register profile in Laravel backend (role is immutable)
      final response = await ApiClient().dio.post(
        '/auth/register-profile',
        data: {
          'name': name.trim(),
          'role': role,
        },
      );

      if (response.statusCode == 201 && response.data['data'] != null) {
        final profile = UserProfile.fromJson(response.data['data']);
        state = state.copyWith(
          status: AuthStatus.authenticated,
          user: profile,
          firebaseUser: fbUser,
          isLoading: false,
        );
        FcmService().registerCurrentToken();
        return true;
      }
      return false;
    } on FirebaseAuthException catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.message ?? 'Registration failed',
      );
      return false;
    } on DioException catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.response?.data?['message'] ?? e.message,
      );
      return false;
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString(),
      );
      return false;
    }
  }

  Future<bool> registerProfile({required String name, required String role}) async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final response = await ApiClient().dio.post(
        '/auth/register-profile',
        data: {
          'name': name.trim(),
          'role': role,
        },
      );

      if ((response.statusCode == 201 || response.statusCode == 200) && response.data['data'] != null) {
        final profile = UserProfile.fromJson(response.data['data']);
        state = state.copyWith(
          status: AuthStatus.authenticated,
          user: profile,
          isLoading: false,
        );
        FcmService().registerCurrentToken();
        return true;
      }
      return false;
    } on DioException catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.response?.data?['message'] ?? e.message,
      );
      return false;
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString(),
      );
      return false;
    }
  }

  Future<void> signOut() async {
    await FirebaseAuth.instance.signOut();
    state = AuthState.initial().copyWith(status: AuthStatus.unauthenticated);
  }
}

final authProvider = NotifierProvider<AuthNotifier, AuthState>(() {
  return AuthNotifier();
});

