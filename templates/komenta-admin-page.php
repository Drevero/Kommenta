<div class="wrap kommenta-settings-wrap">

            <div class="kommenta-header">
                <div class="kommenta-logo">
                    <img src="<?= plugins_url('../assets/kommenta-logo.png', __FILE__); ?>" alt="Kommenta logo"/>
                </div>
                <h1>
                    Kommenta
                    <small><?php echo esc_html__('Configure the types of votes users can give to comments.', 'kommenta'); ?></small>
                </h1>
            </div>

            <div class="kommenta-card">
                <div class="kommenta-card-header">
                    <h2><?php echo esc_html__('Vote Types', 'kommenta'); ?></h2>
                </div>

                <div class="kommenta-card-body">
                    <div id="kommenta-vote-types-list">
                        <?php foreach ($vote_types as $index => $type) : ?>
                        <div class="kommenta-vote-type-row" data-index="<?php echo $index; ?>">
                            <input type="text" class="kommenta-color-picker vote-type-slug-hidden" value="<?php echo esc_attr($type['color']); ?>">
                            <input type="hidden" class="vote-type-slug" value="<?php echo esc_attr($type['slug']); ?>">
                            <div class="vote-type-fields">
                                <div class="vote-type-label-wrap">
                                    <input type="text" class="vote-type-label" value="<?php echo esc_attr($type['label']); ?>" placeholder="<?php echo esc_attr__('Vote label', 'kommenta'); ?>">
                                </div>
                                <span class="vote-type-slug-badge"><?php echo esc_html($type['slug']); ?></span>
                            </div>
                            <button type="button" class="button kommenta-remove-type" title="<?php echo esc_attr__('Remove', 'kommenta'); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="kommenta-add-zone">
                        <button type="button" class="button" id="kommenta-add-type">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php echo esc_html__('Add Vote Type', 'kommenta'); ?>
                        </button>
                    </div>
                </div>

                <div class="kommenta-card-footer">
                    <button type="button" class="button" id="kommenta-reset-defaults">
                        <?php echo esc_html__('Reset to Defaults', 'kommenta'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="kommenta-save-settings">
                        <?php echo esc_html__('Save Settings', 'kommenta'); ?>
                    </button>
                </div>
            </div>

            <div class="kommenta-card mt-5">
                <div class="kommenta-card-header">
                    <h2><?php echo esc_html__('Configure notifications', 'kommenta'); ?> <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#000000" viewBox="0 0 256 256"><path d="M224,71.1a8,8,0,0,1-10.78-3.42,94.13,94.13,0,0,0-33.46-36.91,8,8,0,1,1,8.54-13.54,111.46,111.46,0,0,1,39.12,43.09A8,8,0,0,1,224,71.1ZM35.71,72a8,8,0,0,0,7.1-4.32A94.13,94.13,0,0,1,76.27,30.77a8,8,0,1,0-8.54-13.54A111.46,111.46,0,0,0,28.61,60.32,8,8,0,0,0,35.71,72Zm186.1,103.94A16,16,0,0,1,208,200H167.2a40,40,0,0,1-78.4,0H48a16,16,0,0,1-13.79-24.06C43.22,160.39,48,138.28,48,112a80,80,0,0,1,160,0C208,138.27,212.78,160.38,221.81,175.94ZM150.62,200H105.38a24,24,0,0,0,45.24,0Z"></path></svg></h2>
                </div>

                <div class="kommenta-card-body">
                    <div class="kommenta-notification-row">
                        <div class="kommenta-notification-info">
                            <span class="kommenta-notification-title"><?php echo esc_html__('Email notifications', 'kommenta'); ?></span>
                            <span class="kommenta-notification-desc"><?php echo esc_html__('Send an email to the comment author when someone reacts to their comment.', 'kommenta'); ?></span>
                        </div>
                        <label class="kommenta-toggle" for="kommenta-notify-toggle">
                            <input type="checkbox" id="kommenta-notify-toggle" <?php checked($notify_enabled); ?>>
                            <span class="kommenta-toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="kommenta-toast" id="kommenta-toast"></div>
        </div>

        <script type="text/html" id="tmpl-kommenta-vote-type-row">
            <div class="kommenta-vote-type-row new-row" data-index="{{data.index}}">
                <input type="text" class="kommenta-color-picker vote-type-slug-hidden" value="#cccccc">
                <input type="hidden" class="vote-type-slug" value="">
                <div class="vote-type-fields">
                    <div class="vote-type-label-wrap">
                        <input type="text" class="vote-type-label" value="" placeholder="<?php echo esc_attr__('Vote label', 'kommenta'); ?>">
                    </div>
                    <span class="vote-type-slug-badge"><?php echo esc_html__('Auto-generated', 'kommenta'); ?></span>
                </div>
                <button type="button" class="button kommenta-remove-type" title="<?php echo esc_attr__('Remove', 'kommenta'); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        </script>