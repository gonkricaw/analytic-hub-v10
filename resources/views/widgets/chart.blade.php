{{-- Chart Widget Template --}}
<div class="widget-chart" data-widget-id="{{ $widget->id }}">
    <div class="widget-header">
        <h5 class="widget-title">{{ $widget->title }}</h5>
        @if($widget->description)
            <p class="widget-description">{{ $widget->description }}</p>
        @endif
    </div>
    
    <div class="widget-content">
        <div class="chart-container">
            <canvas id="chart-{{ $widget->id }}" width="400" height="200"></canvas>
        </div>
        
        <div class="widget-data">
            <p><strong>Type:</strong> {{ $data['type'] }}</p>
            <p><strong>Last Updated:</strong> {{ $data['timestamp']->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
    
    <div class="widget-footer">
        <small class="text-muted">Widget ID: {{ $widget->id }}</small>
    </div>
</div>

<style>
.widget-chart {
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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

.chart-container {
    margin-bottom: 15px;
    text-align: center;
}

.widget-data {
    font-size: 0.9em;
}

.widget-footer {
    margin-top: 15px;
    padding-top: 10px;
    border-top: 1px solid #eee;
}
</style>