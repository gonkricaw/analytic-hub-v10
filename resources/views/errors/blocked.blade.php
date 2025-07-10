<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Blocked - {{ config('app.name', 'Analytics Hub') }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .blocked-container {
            max-width: 600px;
            width: 100%;
            padding: 2rem;
        }
        .blocked-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }
        .blocked-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }
        .blocked-body {
            padding: 2rem;
        }
        .blocked-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .info-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #ff6b6b;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.25rem;
        }
        .info-value {
            color: #6c757d;
            font-family: 'Courier New', monospace;
        }
        .btn-contact {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        .btn-contact:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }
        .countdown {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ff6b6b;
        }
    </style>
</head>
<body>
    <div class="blocked-container">
        <div class="blocked-card">
            <div class="blocked-header">
                <div class="blocked-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <h1 class="display-4 mb-3">Access Blocked</h1>
                <p class="lead mb-0">Your IP address has been temporarily blocked due to security violations.</p>
            </div>
            
            <div class="blocked-body">
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Security Notice:</strong> {{ $message ?? 'Access denied due to suspicious activity.' }}
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-globe me-2"></i>Your IP Address
                            </div>
                            <div class="info-value">{{ $ip ?? request()->ip() }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-clock me-2"></i>Blocked At
                            </div>
                            <div class="info-value">
                                {{ isset($blocked_at) ? $blocked_at->format('M d, Y H:i:s') : now()->format('M d, Y H:i:s') }}
                            </div>
                        </div>
                    </div>
                </div>
                
                @if(isset($reason))
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-info-circle me-2"></i>Reason
                    </div>
                    <div class="info-value">{{ $reason }}</div>
                </div>
                @endif
                
                @if(isset($expires_at) && $expires_at)
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-hourglass-half me-2"></i>Block Expires
                    </div>
                    <div class="info-value">
                        {{ $expires_at->format('M d, Y H:i:s') }}
                        <div class="countdown mt-2" id="countdown"></div>
                    </div>
                </div>
                @else
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    This is a permanent block. Please contact the administrator for assistance.
                </div>
                @endif
                
                <div class="mt-4">
                    <h5 class="mb-3">
                        <i class="fas fa-lightbulb me-2 text-warning"></i>
                        What can you do?
                    </h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Wait for the block to expire automatically
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Contact the system administrator if you believe this is an error
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Review your recent activities for any security violations
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Ensure your network security is up to date
                        </li>
                    </ul>
                </div>
                
                <div class="text-center mt-4">
                    <a href="mailto:admin@{{ request()->getHost() }}" class="btn-contact">
                        <i class="fas fa-envelope me-2"></i>
                        Contact Administrator
                    </a>
                </div>
                
                <div class="text-center mt-4">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        This security measure helps protect our system and users.
                        <br>
                        Incident ID: {{ strtoupper(substr(md5($ip ?? request()->ip() . time()), 0, 8)) }}
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @if(isset($expires_at) && $expires_at)
    <script>
        // Countdown timer
        function updateCountdown() {
            const expiresAt = new Date('{{ $expires_at->toISOString() }}');
            const now = new Date();
            const diff = expiresAt - now;
            
            if (diff <= 0) {
                document.getElementById('countdown').innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Block has expired. You may try accessing the site again.</span>';
                return;
            }
            
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            document.getElementById('countdown').innerHTML = 
                `<i class="fas fa-clock me-1"></i>Time remaining: ${hours}h ${minutes}m ${seconds}s`;
        }
        
        // Update countdown every second
        updateCountdown();
        setInterval(updateCountdown, 1000);
    </script>
    @endif
</body>
</html>