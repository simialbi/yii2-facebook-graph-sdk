<?php
/**
 * @package yii2-facebook-graph-sdk
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\facebook\widget;


use simialbi\yii2\widgets\Widget;
use yii\helpers\Html;

class PostToFacebook extends Widget
{
    public $shareIcon = '&#x21AA;';

    public $linkOptions = [];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        $this->registerTranslations();

        Html::addCssClass($this->options, ['position-relative']);
        echo Html::beginTag('div', $this->options);

        ob_start();
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $content = ob_get_clean();

        Html::addCssClass($this->linkOptions, ['position-absolute']);
        Html::addCssStyle($this->linkOptions, [
            'top' => '15px',
            'right' => '15px'
        ]);
        $content .= Html::a($this->shareIcon, ['feed/post'], $this->linkOptions);

        return $content;
    }
}