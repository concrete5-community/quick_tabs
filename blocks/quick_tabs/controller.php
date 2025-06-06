<?php

namespace Concrete\Package\QuickTabs\Block\QuickTabs;

use Concrete\Core\Block\BlockController;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Form\Service\Form;
use Concrete\Core\Page\Page;

defined('C5_EXECUTE') or die('Access Denied.');

class Controller extends BlockController
{
    /**
     * @var string
     */
    const OPENCLOSE_OPEN = 'open';

    /**
     * @var string
     */
    const OPENCLOSE_CLOSE = 'close';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::$btTable
     */
    protected $btTable = 'btQuickTabs';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::$btInterfaceWidth
     */
    protected $btInterfaceWidth = 400;

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::$btInterfaceHeight
     */
    protected $btInterfaceHeight = 365;

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::$helpers
     */
    protected $helpers = [];

    /**
     * @var string|null
     */
    protected $openclose;

    /**
     * @var string|null
     */
    protected $tabTitle;

    /**
     * @var string|null
     */
    protected $semantic;

    /**
     * @var string|null
     */
    protected $tabHandle;

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::getBlockTypeName()
     */
    public function getBlockTypeName()
    {
        return t('Quick Tabs');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::getBlockTypeDescription()
     */
    public function getBlockTypeDescription()
    {
        return t('Add Tabs to the Page');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::ignorePageThemeGridFrameworkContainer()
     */
    public function ignorePageThemeGridFrameworkContainer()
    {
        $c = Page::getCurrentPage();

        return $c && !$c->isError() && $c->isEditMode() ? false : true;
    }

    public function view()
    {
        $page = Page::getCurrentPage();
        if (!$page || $page->isError()) {
            $page = null;
        }
        $this->set('page', $page);
        $this->set('isEditMode', $page !== null && $page->isEditMode());
    }

    public function add()
    {
        $this->set('openclose', '');
        $this->set('tabTitle', '');
        $this->set('semantic', '');
        $this->set('opencloseOptions', ['' => ''] + $this->getOpencloseOptions());
        $this->set('tabHandle', '');
        $this->addOrEdit();
    }

    public function edit()
    {
        // Previous version defined 'H4' instead of 'h4'
        $semanticOptions = $this->getSemanticOptions();
        if (!isset($semanticOptions[$this->semantic]) && $this->semantic === 'H4' && isset($semanticOptions['h4'])) {
            $this->set('semantic', 'h4');
        }
        $this->set('opencloseOptions', $this->getOpencloseOptions());
        $this->addOrEdit();
    }

    /**
     * @param \Concrete\Core\Page\Page|null $page
     * @param \Concrete\Core\Area\Area|null $area
     *
     * @return \Concrete\Core\Page\Theme\GridFramework\GridFramework|null
     */
    public function getAreaGridFramework($page, $area)
    {
        if (!$page || $page->isError() || !$area || $area->isError() || !$area->isGridContainerEnabled()) {
            return null;
        }
        $theme = $page->getCollectionThemeObject();
        if (!$theme || !$theme->supportsGridFramework()) {
            return null;
        }

        return $theme->getThemeGridFrameworkObject() ?: null;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::validate()
     */
    public function validate($args)
    {
        $result = $this->normalizeArgs($args);

        return is_array($result) ? true : $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::save()
     */
    public function save($args)
    {
        $result = $this->normalizeArgs($args);
        if (!is_array($result)) {
            throw new UserMessageException(implode("\n", $result->getList()));
        }
        parent::save($result);
    }

    private function addOrEdit()
    {
        $this->set('form', $this->app->make(Form::class));
        $this->set('semanticOptions', $this->getSemanticOptions());
    }

    /**
     * @param mixed $args
     *
     * @return array|object error object in case of errors
     */
    private function normalizeArgs($args)
    {
        $args = (is_array($args) ? $args : []) + [
            'openclose' => '',
            'tabTitle' => '',
            'semantic' => '',
            'tabHandle' => '',
        ];
        $errors = $this->app->make('helper/validation/error');
        $result = [
            'openclose' => (string) $args['openclose'],
            'tabTitle' => '',
            'semantic' => '',
            'tabHandle' => '',
        ];
        $opencloseOptions = $this->getOpencloseOptions();
        if ($result['openclose'] === '' || !isset($opencloseOptions[$result['openclose']])) {
            $errors->add(t('Is this the Opening or Closing Block?'));
        } elseif ($result['openclose'] === static::OPENCLOSE_OPEN) {
            $result['tabTitle'] = is_string($args['tabTitle']) ? $args['tabTitle'] : '';
            if ($result['tabTitle'] === '') {
                $errors->add(t('Please specify the Tag Title.'));
            }
            $result['semantic'] = is_string($args['semantic']) ? $args['semantic'] : '';
            $semanticOptions = $this->getSemanticOptions();
            if ($result['semantic'] === '' || !isset($semanticOptions[$result['semantic']])) {
                $errors->add(t('Please specify the Semantic Tag for the Tab Title.'));
            }
            $result['tabHandle'] = is_string($args['tabHandle']) ? trim($args['tabHandle']) : '';
            $invalidChars = ':#|';
            if ($result['tabHandle'] !== '' && strpbrk($result['tabHandle'], $invalidChars) !== false) {
                $errors->add(
                    t(
                        "Tab Handle can't contain these characters: %s",
                        '"' . implode('", "', str_split($invalidChars, 1)) . '"'
                    )
                );
            }
        }

        return $errors->has() ? $errors : $result;
    }

    /**
     * Get the list of allowed values for the openclose field.
     *
     * @return array
     */
    private function getOpencloseOptions()
    {
        return [
            static::OPENCLOSE_OPEN => t('Open'),
            static::OPENCLOSE_CLOSE => t('Close'),
        ];
    }

    /**
     * Get the list of allowed HTML tags.
     *
     * @return array
     */
    private function getSemanticOptions()
    {
        $config = $this->app->make('config');
        $tags = preg_split('/\W+/', (string) $config->get('quick_tabs::options.custom_tags'), -1, PREG_SPLIT_NO_EMPTY);
        if ($tags !== []) {
            return array_combine($tags, $tags);
        }

        return [
            'h2' => h('Title 2'),
            'h3' => h('Title 3'),
            'h4' => h('Title 4'),
            'p' => t('Paragraph'),
            'span' => tc('HTML Element', 'Span'),
        ];
    }
}
