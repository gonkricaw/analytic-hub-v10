{{-- Table Widget Template --}}
<div class="widget-table" data-widget-id="{{ $widget->id }}">
    <div class="widget-header">
        <h5 class="widget-title">{{ $widget->title }}</h5>
        @if($widget->description)
            <p class="widget-description">{{ $widget->description }}</p>
        @endif
    </div>
    
    <div class="widget-content">
        <div class="table-container">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Sample Data</td>
                        <td>100</td>
                        <td><span class="badge badge-success">Active</span></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Test Data</td>
                        <td>250</td>
                        <td><span class="badge badge-warning">Pending</span></td>
                    </tr>
                </tbody>
            </table>
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
.widget-table {
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

.table-container {
    margin-bottom: 15px;
    overflow-x: auto;
}

.table {
    margin-bottom: 0;
}

.widget-data {
    font-size: 0.9em;
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

.badge-warning {
    background-color: #ffc107;
    color: black;
}
</style>