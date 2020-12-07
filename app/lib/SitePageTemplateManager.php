<?php
/** ---------------------------------------------------------------------
 * app/lib/SitePageTemplateManager.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2019 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage ContentManagement
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */
require_once(__CA_LIB_DIR__ . "/View.php");
require_once(__CA_MODELS_DIR__ . "/ca_site_templates.php");

class SitePageTemplateManager
{
    # -------------------------------------------------------
    /**
     *
     */
    public static function getTemplateDirectories()
    {
        return [__CA_THEME_DIR__ . '/templates', __CA_APP_DIR__ . '/conf/local/templates'];
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function getTemplateNames()
    {
        $template_dirs = SitePageTemplateManager::getTemplateDirectories();

        $va_templates = [];
        foreach ($template_dirs as $template_dir) {
            if (!file_exists($template_dir)) {
                continue;
            }

            if (is_resource($r_dir = opendir($template_dir))) {
                while (($vs_template = readdir($r_dir)) !== false) {
                    if (file_exists($template_dir . '/' . $vs_template) && preg_match(
                            "/^([A-Za-z_]+[A-Za-z0-9_]*)\.tmpl$/",
                            $vs_template,
                            $va_matches
                        )) {
                        $va_templates[$template_dir][] = $va_matches[1];
                    }
                }
            }
        }
        foreach ($va_templates as $d => $t) {
            sort($t);
        }

        return $va_templates;
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function scan()
    {
        $template_dirs = SitePageTemplateManager::getTemplateDirectories();
        $va_template_names = SitePageTemplateManager::getTemplateNames();

        $vn_template_insert_count = $vn_template_update_count = 0;
        $va_errors = [];
        foreach ($template_dirs as $template_dir) {
            if (!is_array($va_template_names[$template_dir])) {
                continue;
            }
            foreach ($va_template_names[$template_dir] as $vs_template_name) {
                $vs_template_path = "{$template_dir}/{$vs_template_name}.tmpl";
                $vs_template_content = file_get_contents($vs_template_path);

                $o_view = new View(null, $vs_template_path);
                $va_tags = $o_view->getTagList($vs_template_path);

                $va_restricted_tag_names = [
                    'page_title',
                    'page_description',
                    'page_path',
                    'page_access',
                    'page_keywords',
                    'page_view_count'
                ];    // these are the names of the built-in tags
                $va_tags_with_info = [];
                $va_config = self::getTemplateConfig()->get('fields');
                foreach ($va_tags as $vs_tag) {
                    $tag_parsed = caParseTagOptions($vs_tag);
                    $vs_tag_name = $tag_parsed['tag'];
                    if (in_array($vs_tag_name, $va_restricted_tag_names)) {
                        continue;
                    }
                    if (preg_match("!^media:!", $vs_tag_name)) {
                        continue;
                    }

                    $va_tags_with_info[$vs_tag_name] = [];
                    if (is_array($tag_parsed['options'])) {
                        $tag_parsed['options'] = array_map(
                            function ($v) {
                                return urldecode($v);
                            },
                            $tag_parsed['options']
                        );
                        $va_tags_with_info[$vs_tag_name] = $tag_parsed['options'];
                    }
                    if (is_array($va_config[$vs_tag_name])) {
                        foreach ($va_config[$vs_tag_name] as $k => $v) {
                            if (!isset($va_tags_with_info[$vs_tag_name][$k])) {
                                $va_tags_with_info[$vs_tag_name][$k] = $v;
                            }
                        }
                    }
                    $va_tags_with_info[$vs_tag_name]['code'] = $vs_tag_name;

                    $vs_template_content = str_replace($vs_tag, $vs_tag_name, $vs_template_content);
                }

                $t_template = new ca_site_templates();

                if ($t_template->load(['template_code' => $vs_template_name])) {
                    $t_template->setMode(ACCESS_WRITE);

                    $t_template->purify(false);
                    $t_template->set(
                        [
                            'template' => $vs_template_content,
                            'tags' => $va_tags_with_info,
                            'deleted' => 0
                        ]
                    );
                    $t_template->update();
                    if (!$t_template->numErrors()) {
                        $vn_template_update_count++;
                    }
                } else {
                    $t_template->setMode(ACCESS_WRITE);
                    $t_template->purify(false);
                    $t_template->set(
                        [
                            'template_code' => $vs_template_name,
                            'title' => $vs_template_name,
                            'description' => '',
                            'template' => $vs_template_content,
                            'tags' => $va_tags_with_info,
                            'deleted' => 0
                        ]
                    );
                    $t_template->insert();
                    if (!$t_template->numErrors()) {
                        $vn_template_insert_count++;
                    }
                }
                if ($t_template->numErrors()) {
                    $va_errors[$vs_template_name] = $t_template->getErrors();
                }
            }
        }

        return ['insert' => $vn_template_insert_count, 'update' => $vn_template_update_count, 'errors' => $va_errors];
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function getTemplateConfig()
    {
        return Configuration::load(__CA_THEME_DIR__ . "/conf/templates.conf");
    }
    # -------------------------------------------------------
}
