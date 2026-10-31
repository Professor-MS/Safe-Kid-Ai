class UserProfile {
  final int id;
  final String firebaseUid;
  final String name;
  final String email;
  final String role; // 'parent' | 'child'
  final int? batteryLevel;
  final DateTime? lastSeenAt;

  const UserProfile({
    required this.id,
    required this.firebaseUid,
    required this.name,
    required this.email,
    required this.role,
    this.batteryLevel,
    this.lastSeenAt,
  });

  bool get isParent => role == 'parent';
  bool get isChild => role == 'child';

  factory UserProfile.fromJson(Map<String, dynamic> json) {
    return UserProfile(
      id: json['id'] as int,
      firebaseUid: json['firebase_uid'] as String,
      name: json['name'] as String,
      email: json['email'] as String? ?? '',
      role: json['role'] as String,
      batteryLevel: json['battery_level'] as int?,
      lastSeenAt: json['last_seen_at'] != null
          ? DateTime.tryParse(json['last_seen_at'] as String)
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'firebase_uid': firebaseUid,
      'name': name,
      'email': email,
      'role': role,
      'battery_level': batteryLevel,
      'last_seen_at': lastSeenAt?.toIso8601String(),
    };
  }
}

