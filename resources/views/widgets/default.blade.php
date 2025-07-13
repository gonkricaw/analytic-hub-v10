{{-- Default Widget Template --}}
<div class="widget-default" data-widget-id="{{ $widget->id }}">
    <div class="widget-header">
        <h5 class="widget-title">{{ $widget->title }}</h5>
        @if($widget->description)
            <p class="widget-description">{{ $widget->description }}</p>
        @endif
    </div>
    
    <div class="widget-content">
        <div class="widget-info">
            <p><strong>Widget Type:</strong> {{ $data['type'] }}</p>
            <p><strong>Widget ID:</strong> {{ $widget->id }}</p>
            <p><strong>Status:</strong> 
                @if($widget->is_active)
                    <span class="badge badge-success">Active</span>
                @else
                    <span class="badge badge-secondary">Inactive</span>
                @endif
            </p>
        </div>
        
        <div class="widget-data">
            <p><strong>Last Updated:</strong> {{ $data['timestamp']->format('Y-m-d H:i:s') }}</p>
            @if(!empty($data['data']))
                <div class="data-preview">
                    <strong>Data:</strong>
                    <pre>{{ json_encode($data['data'], JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        </div>
    </div>
    
    <div class="widget-footer">
        <small class="text-muted">Default Widget Template</small>
    </div>
</div>

<style>
.widget-default {
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #007bff;
}

.widget-header {
    margin-bottom: 15px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.widget-title {
    margin: 0;
    color: #333;
    font-size: 1.2em;
}

.widget-description {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 0.9em;
}

.widget-info {
    margin-bottom: 15px;
}

.widget-data {
    font-size: 0.9em;
}

.data-preview {
    margin-top: 10px;
}

.data-preview pre {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    font-size: 0.8em;
    max-height: 200px;
    overflow-y: auto;
}

.widget-footer {
    margin-top: 15px;
    padding-top: 10px;
    border-top: 1px solid #eee;
}

.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
}

.badge-success {
    background-color: #28a745;
    color: white;
}

.badge-secondary {
    background-color: #6c757d;
    color: white;
}
</style>