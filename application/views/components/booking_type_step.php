<?php
/**
 * Local variables.
 *
 * @var array $available_services
 */
?>

<style>
    .ea-multi-category {
        border: 1px solid #e9ecef;
        border-radius: 6px;
        margin-bottom: 10px;
    }

    .ea-multi-category-header {
        cursor: pointer;
        user-select: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 6px 10px;
        background-color: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        border-top-left-radius: 6px;
        border-top-right-radius: 6px;
    }

    .ea-multi-category-body {
        padding: 8px 10px;
    }

    .ea-multi-category-body.ea-collapsed {
        display: none;
    }

    .category-caret {
        font-size: 12px;
        color: #6c757d;
    }
</style>

<div id="wizard-frame-1" class="wizard-frame" style="visibility: hidden;">
    <div class="frame-container">
        <h2 class="frame-title mt-md-5"><?= lang('service_and_provider') ?></h2>

        <div class="row frame-content">
            <div class="col col-md-8 offset-md-2">
                <div class="mb-3">
                    <label for="select-service">
                        <strong><?= lang('service') ?></strong>
                    </label>

                    <select id="select-service" class="form-select d-none">
                        <option value="">
                            <?= lang('please_select') ?>
                        </option>
                        <?php
                        // Group services by category, only if there is at least one service with a parent category.
                        $has_category = false;
                        foreach ($available_services as $service) {
                            if (!empty($service['service_category_id'])) {
                                $has_category = true;
                                break;
                            }
                        }

                        if ($has_category) {
                            $grouped_services = [];

                            foreach ($available_services as $service) {
                                if (!empty($service['service_category_id'])) {
                                    if (!isset($grouped_services[$service['service_category_name']])) {
                                        $grouped_services[$service['service_category_name']] = [];
                                    }

                                    $grouped_services[$service['service_category_name']][] = $service;
                                }
                            }

                            // We need the uncategorized services at the end of the list, so we will use another
                            // iteration only for the uncategorized services.
                            $grouped_services['uncategorized'] = [];
                            foreach ($available_services as $service) {
                                if ($service['service_category_id'] == null) {
                                    $grouped_services['uncategorized'][] = $service;
                                }
                            }

                            foreach ($grouped_services as $key => $group) {
                                $group_label =
                                    $key !== 'uncategorized' ? $group[0]['service_category_name'] : 'Uncategorized';

                                if (count($group) > 0) {
                                    echo '<optgroup label="' . e($group_label) . '">';
                                    foreach ($group as $service) {
                                        echo '<option value="' .
                                            $service['id'] .
                                            '">' .
                                            e($service['name']) .
                                            '</option>';
                                    }
                                    echo '</optgroup>';
                                }
                            }
                        } else {
                            foreach ($available_services as $service) {
                                echo '<option value="' . $service['id'] . '">' . e($service['name']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <small class="text-muted d-block mt-1">
                        You can select multiple services for a single visit.
                    </small>

                    <div id="service-picker" class="mt-3">
                        <?php
                        $grouped_services = [];
                        foreach ($available_services as $service) {
                            $category_name = $service['service_category_name'] ?? 'Uncategorized';
                            if (!isset($grouped_services[$category_name])) {
                                $grouped_services[$category_name] = [];
                            }
                            $grouped_services[$category_name][] = $service;
                        }

                        if (empty($grouped_services)) {
                            $grouped_services['Services'] = $available_services;
                        }

                        $slugify = function ($text, $fallback) {
                            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $text));
                            $slug = trim($slug, '-');

                            return !empty($slug) ? $slug : $fallback;
                        };

                        foreach ($grouped_services as $category_name => $services): ?>
                            <?php
                            $category_id = $services[0]['service_category_id'] ?? null;
                            $body_id = 'category-' . e($slugify($category_name, $category_id ?: uniqid()));
                            ?>
                            <div class="ea-multi-category">
                                <div class="ea-multi-category-header js-category-toggle" data-target="<?= $body_id; ?>">
                                    <span><?= e($category_name); ?></span>
                                    <span class="category-caret">▾</span>
                                </div>
                                <div class="ea-multi-category-body" id="<?= $body_id; ?>">
                                    <?php foreach ($services as $service): ?>
                                        <div class="form-check mb-1">
                                            <input
                                                type="checkbox"
                                                class="form-check-input js-multi-service-checkbox"
                                                data-service-id="<?= e($service['id']); ?>"
                                                id="service-checkbox-<?= e($service['id']); ?>"
                                            >
                                            <label class="form-check-label" for="service-checkbox-<?= e($service['id']); ?>">
                                                <?= e($service['name']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php slot('after_select_service'); ?>

                <div class="mb-3">
                    <label for="select-provider">
                        <strong><?= lang('provider') ?></strong>
                    </label>

                    <select id="select-provider" class="form-select">
                        <option value="">
                            <?= lang('please_select') ?>
                        </option>
                    </select>
                </div>

                <?php slot('after_select_provider'); ?>

                <div id="service-description" class="small">
                    <!-- JS -->
                </div>

                <?php slot('after_service_description'); ?>

            </div>
        </div>
    </div>

    <div class="command-buttons">
        <span>&nbsp;</span>

        <button type="button" id="button-next-1" class="btn button-next btn-dark"
                data-step_index="1">
            <?= lang('next') ?>
            <i class="fas fa-chevron-right ms-2"></i>
        </button>
    </div>
</div>
