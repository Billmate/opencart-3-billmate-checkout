<?php
require_once(DIR_APPLICATION . 'controller/billmatecheckout/CoreBmController.php');

class FrontBmController extends CoreBmController
{
    /**
     * @var array
     */
    protected $templateData = [];

    /**
     * @return array
     */
    protected function loadBreadcrumbs() {
        $breadcrumbs[] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        ];

        $breadcrumbs[] = [
            'text' => $this->language->get('text_basket'),
            'href' => $this->url->link('checkout/cart')
        ];

        $breadcrumbs[] = [
            'text' => $this->language->get('text_checkout'),
            'href' => $this->url->link('checkout/checkout', '', true)
        ];

        $breadcrumbs[] = [
            'text' => $this->language->get('text_success'),
            'href' => $this->url->link('billmatecheckout/accept')
        ];
        $this->templateData['breadcrumbs'] = $breadcrumbs;
        return $this;
    }

    /**
     * @return $this
     */
    protected function loadBaseBlocks()
    {
        $this->templateData['continue'] = $this->url->link('common/home');
        $this->templateData['column_left'] = $this->load->controller('common/column_left');
        $this->templateData['column_right'] = $this->load->controller('common/column_right');
        $this->templateData['content_top'] = $this->load->controller('common/content_top');
        $this->templateData['content_bottom'] = $this->load->controller('common/content_bottom');
        $this->templateData['footer'] = $this->load->controller('common/footer');
        $this->templateData['header'] = $this->load->controller('common/header');
        return $this;
    }

    /**
     * @return array
     */
    protected function getTemplateData()
    {
        return $this->templateData;
    }
}