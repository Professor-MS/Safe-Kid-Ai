import 'package:familytrack/features/auth/providers/auth_provider.dart';
import 'package:familytrack/features/auth/screens/login_screen.dart';
import 'package:familytrack/features/auth/screens/register_screen.dart';
import 'package:familytrack/features/auth/screens/role_selection_screen.dart';
import 'package:familytrack/features/auth/screens/splash_screen.dart';
import 'package:familytrack/features/home/screens/child_home_screen.dart';
import 'package:familytrack/features/home/screens/parent_home_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final authNotifier = ValueNotifier<AuthState>(ref.watch(authProvider));

  ref.listen<AuthState>(authProvider, (_, next) {
    authNotifier.value = next;
  });

  return GoRouter(
    initialLocation: '/splash',
    refreshListenable: authNotifier,
    redirect: (BuildContext context, GoRouterState state) {
      final authState = ref.read(authProvider);
      final currentLoc = state.matchedLocation;

      // While initializing, stay on splash screen
      if (authState.status == AuthStatus.initial) {
        return currentLoc == '/splash' ? null : '/splash';
      }

      // If not logged in, allow login or register screens
      if (authState.status == AuthStatus.unauthenticated) {
        if (currentLoc == '/login' || currentLoc == '/register') {
          return null;
        }
        return '/login';
      }

      // If authenticated in Firebase but profile incomplete in backend
      if (authState.status == AuthStatus.needsRoleSelection) {
        return currentLoc == '/role-selection' ? null : '/role-selection';
      }

      // If authenticated with full profile, route by immutable role
      if (authState.status == AuthStatus.authenticated && authState.user != null) {
        final isParent = authState.user!.isParent;
        final targetHome = isParent ? '/parent-home' : '/child-home';

        // Redirect away from auth routes to appropriate home
        if (currentLoc == '/login' ||
            currentLoc == '/register' ||
            currentLoc == '/splash' ||
            currentLoc == '/role-selection') {
          return targetHome;
        }

        // Prevent cross-role navigation
        if (isParent && currentLoc.startsWith('/child-home')) {
          return '/parent-home';
        }
        if (!isParent && currentLoc.startsWith('/parent-home')) {
          return '/child-home';
        }

        return null;
      }

      return null;
    },
    routes: [
      GoRoute(
        path: '/splash',
        builder: (context, state) => const SplashScreen(),
      ),
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: '/register',
        builder: (context, state) => const RegisterScreen(),
      ),
      GoRoute(
        path: '/role-selection',
        builder: (context, state) => const RoleSelectionScreen(),
      ),
      GoRoute(
        path: '/parent-home',
        builder: (context, state) => const ParentHomeScreen(),
      ),
      GoRoute(
        path: '/child-home',
        builder: (context, state) => const ChildHomeScreen(),
      ),
    ],
  );
});

