<div class="wrap kommenta-settings-wrap kommenta-stats-wrap">

    <!-- Header -->
    <div class="kommenta-header">
        <div class="kommenta-logo">
            <img src="<?= plugins_url('../assets/kommenta-logo.png', __FILE__); ?>" alt="Kommenta logo"/>
        </div>
        <h1>
            Kommenta
            <small><?php echo esc_html__('Statistics and insights from your community votes', 'kommenta'); ?></small>
        </h1>
    </div>

    <!-- Summary cards -->
    <div class="kommenta-stats-summary">
        <div class="kommenta-stat-box">
            <span class="kommenta-stat-number"><?php echo esc_html($grand_total_votes); ?></span>
            <span class="kommenta-stat-label"><?php echo esc_html__('Total votes', 'kommenta'); ?></span>
        </div>
        <div class="kommenta-stat-box">
            <span class="kommenta-stat-number"><?php echo esc_html(count($all_comments_stats)); ?></span>
            <span class="kommenta-stat-label"><?php echo esc_html__('Comments with votes', 'kommenta'); ?></span>
        </div>
        <div class="kommenta-stat-box">
            <span class="kommenta-stat-number"><?php echo esc_html(count($vote_types)); ?></span>
            <span class="kommenta-stat-label"><?php echo esc_html__('Emotion types', 'kommenta'); ?></span>
        </div>
    </div>

    <?php if (empty($all_comments_stats)) : ?>
        <div class="kommenta-card">
            <div class="kommenta-card-body">
                <div class="kommenta-empty-state">
                    <span class="dashicons dashicons-chart-bar" style="font-size:40px;width:40px;height:40px;color:#ccc;"></span>
                    <p><?php echo esc_html__('No votes recorded yet. Once your visitors start voting on comments, statistics will appear here.', 'kommenta'); ?></p>
                </div>
            </div>
        </div>
    <?php else : ?>

    <!-- TOP 5 COMMENTS -->
    <div class="kommenta-card">
        <div class="kommenta-card-header">
            <h2>
                <span class="dashicons dashicons-star-filled" style="color:#f59e0b;margin-right:6px;"></span>
                <?php echo esc_html__('Top 5 most voted comments', 'kommenta'); ?>
            </h2>
        </div>
        <div class="kommenta-card-body">
            <?php if (!empty($top_5_comments)) : ?>
                <table class="kommenta-stats-table">
                    <thead>
                        <tr>
                            <th class="kommenta-col-rank">#</th>
                            <th><?php echo esc_html__('Comment', 'kommenta'); ?></th>
                            <th><?php echo esc_html__('Author', 'kommenta'); ?></th>
                            <th><?php echo esc_html__('Post', 'kommenta'); ?></th>
                            <th class="kommenta-col-emotions"><?php echo esc_html__('Emotions', 'kommenta'); ?></th>
                            <th class="kommenta-col-votes"><?php echo esc_html__('Votes', 'kommenta'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_5_comments as $rank => $item) : ?>
                            <tr>
                                <td class="kommenta-col-rank">
                                    <span class="kommenta-rank-badge"><?php echo esc_html($rank + 1); ?></span>
                                </td>
                                <td class="kommenta-col-comment">
                                    <?php echo esc_html($item['comment_content']); ?>
                                </td>
                                <td class="kommenta-col-author">
                                    <?php echo esc_html($item['comment_author']); ?>
                                </td>
                                <td class="kommenta-col-post">
                                    <a href="<?php echo esc_url(get_permalink($item['post_id'])); ?>" target="_blank">
                                        <?php echo esc_html(wp_trim_words($item['post_title'], 6, '…')); ?>
                                    </a>
                                </td>
                                <td class="kommenta-col-emotions">
                                    <div class="kommenta-mini-bar">
                                        <?php foreach ($item['emotions'] as $slug => $count) :
                                            if ($count <= 0) continue;
                                            $pct = ($item['total_votes'] > 0) ? ($count / $item['total_votes'] * 100) : 0;
                                            $color = isset($color_map[$slug]) ? $color_map[$slug] : '#ccc';
                                            $label = isset($label_map[$slug]) ? $label_map[$slug] : $slug;
                                        ?>
                                            <span class="kommenta-mini-bar-segment"
                                                  style="width:<?php echo esc_attr($pct); ?>%;background:<?php echo esc_attr($color); ?>;"
                                                  title="<?php echo esc_attr($label . ': ' . $count); ?>"></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="kommenta-col-votes">
                                    <strong><?php echo esc_html($item['total_votes']); ?></strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="kommenta-card">
        <div class="kommenta-card-header">
            <h2>
                <span class="dashicons dashicons-heart" style="color:#e88a94;margin-right:6px;"></span>
                <?php echo esc_html__('Most popular emotions', 'kommenta'); ?>
            </h2>
        </div>
        <div class="kommenta-card-body" style="padding:24px;">
            <div class="kommenta-emotion-grid">
                <?php foreach ($emotion_totals as $slug => $total) :
                    $color = isset($color_map[$slug]) ? $color_map[$slug] : '#ccc';
                    $label = isset($label_map[$slug]) ? $label_map[$slug] : $slug;
                    $pct = ($grand_total_votes > 0) ? round($total / $grand_total_votes * 100, 1) : 0;
                ?>
                    <div class="kommenta-emotion-card">
                        <div class="kommenta-emotion-color-dot" style="background:<?php echo esc_attr($color); ?>;"></div>
                        <div class="kommenta-emotion-info">
                            <span class="kommenta-emotion-label"><?php echo esc_html($label); ?></span>
                            <span class="kommenta-emotion-count"><?php echo esc_html($total); ?> <?php echo esc_html__('votes', 'kommenta'); ?></span>
                        </div>
                        <div class="kommenta-emotion-bar-wrap">
                            <div class="kommenta-emotion-bar" style="width:<?php echo esc_attr($pct); ?>%;background:<?php echo esc_attr($color); ?>;"></div>
                        </div>
                        <span class="kommenta-emotion-pct"><?php echo esc_html($pct); ?>%</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="kommenta-card">
        <div class="kommenta-card-header">
            <h2>
                <span class="dashicons dashicons-list-view" style="color:#4a9ed6;margin-right:6px;"></span>
                <?php echo esc_html__('All voted comments', 'kommenta'); ?>
            </h2>
            <span class="kommenta-badge"><?php echo esc_html(count($all_comments_stats)); ?></span>
        </div>
        <div class="kommenta-card-body">
            <table class="kommenta-stats-table kommenta-stats-table-full">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Comment', 'kommenta'); ?></th>
                        <th><?php echo esc_html__('Author', 'kommenta'); ?></th>
                        <th><?php echo esc_html__('Post', 'kommenta'); ?></th>
                        <th><?php echo esc_html__('Date', 'kommenta'); ?></th>
                        <?php foreach ($vote_types as $type) : ?>
                            <th class="kommenta-col-emotion-count" style="color:<?php echo esc_attr($type['color']); ?>;">
                                <?php echo esc_html($type['label']); ?>
                            </th>
                        <?php endforeach; ?>
                        <th class="kommenta-col-votes"><?php echo esc_html__('Total', 'kommenta'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_comments_stats as $item) : ?>
                        <tr>
                            <td class="kommenta-col-comment">
                                <?php echo esc_html($item['comment_content']); ?>
                            </td>
                            <td class="kommenta-col-author">
                                <?php echo esc_html($item['comment_author']); ?>
                            </td>
                            <td class="kommenta-col-post">
                                <a href="<?php echo esc_url(get_permalink($item['post_id'])); ?>" target="_blank">
                                    <?php echo esc_html(wp_trim_words($item['post_title'], 6, '…')); ?>
                                </a>
                            </td>
                            <td class="kommenta-col-date">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($item['comment_date']))); ?>
                            </td>
                            <?php foreach ($vote_types as $type) :
                                $slug = $type['slug'];
                                $count = isset($item['emotions'][$slug]) ? $item['emotions'][$slug] : 0;
                            ?>
                                <td class="kommenta-col-emotion-count">
                                    <?php if ($count > 0) : ?>
                                        <span class="kommenta-emotion-pill" style="background:<?php echo esc_attr($type['color']); ?>20;color:<?php echo esc_attr($type['color']); ?>;">
                                            <?php echo esc_html($count); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="kommenta-emotion-zero">0</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="kommenta-col-votes">
                                <strong><?php echo esc_html($item['total_votes']); ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>
</div>
