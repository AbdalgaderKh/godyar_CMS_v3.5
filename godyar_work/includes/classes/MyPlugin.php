<?php

class MyPlugin {
    public function init() {
        
        add_action('news_published', [$this, 'onNewsPublished']);
    }
    
    public function onNewsPublished($newsId) {
        
    }
}

$plugin = new MyPlugin();
$plugin->init();
?>