<?php

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\Area\Area $a
 * @var Concrete\Package\QuickTabs\Block\QuickTabs\Controller $controller
 * @var Concrete\Core\Page\Page $page
 * @var bool $isEditMode

 * @var string $openclose
 * @var string $tabTitle
 * @var string $semantic
 * @var string $tabHandle
 */

$tagAttributes = [
    'style' => $isEditMode ? 'padding: 15px; background: #ccc; color: #444; border: 1px solid #999;' : 'visibility: hidden;',
];

if ($openclose !== $controller::OPENCLOSE_CLOSE){
    $tagAttributes['class'] = 'simpleTabsOpen';
    $tag = $semantic;
    if ($isEditMode) {
        $tagContents = t('Opening Tab "%s"', $tabTitle);
        $tagAttributes['class'] .= ' editmode';
    } else {
        $tagContents = $tabTitle;
        $tagAttributes['data-tab-title'] = $tabTitle;
        $tabHandle = (string) $tabHandle;
        if ($tabHandle !== '') {
            $tagAttributes['data-tab-handle'] = $tabHandle;
        }
        $gridFramework = $controller->getAreaGridFramework($page, $a);
        if ($gridFramework !== null) {
            $tagAttributes['data-wrapper-open'] =
                $gridFramework->getPageThemeGridFrameworkContainerStartHTML() .
                $gridFramework->getPageThemeGridFrameworkRowStartHTML() .
                sprintf(
                    '<div class="%s">',
                    $gridFramework->getPageThemeGridFrameworkColumnClassesForSpan(
                        min($a->getAreaGridMaximumColumns(), $gridFramework->getPageThemeGridFrameworkNumColumns())
                        )
                    )
            ;
            $tagAttributes['data-wrapper-close'] =
                '</div>' .
                $gridFramework->getPageThemeGridFrameworkRowEndHTML() .
                $gridFramework->getPageThemeGridFrameworkContainerEndHTML()
            ;
        }
    }
} else {
    $tagAttributes['class'] = 'simpleTabsClose';
    $tag = 'div';
    $tagContents = t('Closing Tab');
}
$tagAttributesString = '';
foreach ($tagAttributes as $tagAttributeName => $tagAttributeValue) {
    $tagAttributesString .= ' ' . h($tagAttributeName) . '="' . h($tagAttributeValue) . '"';
}
printf(
    '<%1$s%2$s>%3$s</%1$s>',
    $tag, // %1$s
    $tagAttributesString,
    $tagContents
);
