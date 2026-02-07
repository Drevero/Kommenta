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