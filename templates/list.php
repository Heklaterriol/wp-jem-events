<?php
/** @var array $events */
if(empty($events)) {
    echo '<p>' . __('No events available','jem-events') . '</p>';
    return;
}
?>
<ul class="jem-events-list">
<?php foreach($events as $e): ?>
    <li class="jem-event-item">
        <span class="jem-event-title">
            <a href="<?php echo esc_url($e['title']['url']); ?>">
                <?php echo esc_html($e['title']['display']); ?>
            </a>
        </span>

        <span class="jem-event-date"><?php echo esc_html($e['dates']['formatted_start_date']); ?></span>
        <span class="jem-event-time"><?php echo esc_html($e['dates']['formatted_start_time']); ?></span>

        <?php if(!empty($e['venue'])): ?>
            <span class="jem-event-venue">
                <a href="<?php echo esc_url($e['venue']['url']); ?>">
                    <?php echo esc_html($e['venue']['name']); ?>
                </a>
            </span>
        <?php endif; ?>
    </li>
<?php endforeach; ?>
</ul>
