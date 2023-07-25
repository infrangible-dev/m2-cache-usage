<?php

namespace Infrangible\CacheUsage\Block\Adminhtml\BlockCache;

use Exception;
use Magento\Framework\Data\Collection\AbstractDb;
use Infrangible\BackendWidget\Block\Grid\GroupBy;
use Infrangible\CacheUsage\Model\Config\Source\Cacheable;
use Zend_Db_Expr;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Grid
    extends GroupBy
{
    /**
     * @param AbstractDb $collection
     *
     * @return void
     */
    protected function prepareCollection(AbstractDb $collection)
    {
    }

    /**
     * @param AbstractDb $collection
     */
    protected function followUpCollection(AbstractDb $collection)
    {
        parent::followUpCollection($collection);

        $groupBy = $this->getParam('group_by');

        if ( ! $this->variableHelper->isEmpty($groupBy)) {
            $select = $collection->getSelect();

            $select->columns([new Zend_Db_Expr('ROUND(AVG(duration), 0) AS average_duration')]);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function prepareFields()
    {
        $this->addTextColumn('route', __('Route'));
        $this->addTextColumn('controller', __('Controller'));
        $this->addTextColumn('action', __('Action'));
        $this->addTextColumn('path', __('Path'));
        $this->addTextColumn('query', __('Query'));
        $this->addTextColumn('layout_name', __('Layout Name'));
        $this->addTextColumn('class_name', __('Class Name'));
        $this->addTextColumn('template_name', __('Template Name'));
        $this->addOptionsClassColumn('cacheable', __('Cacheable'), Cacheable::class);
        $this->addYesNoColumn('cached', __('Cached'));
        $this->addNumberColumn('duration', __('Duration'));
        $this->addDatetimeColumn('created_at', __('Date'));
    }

    /**
     * @return string[]
     */
    protected function getHiddenFieldNames(): array
    {
        return ['path', 'query', 'class_name', 'template_name'];
    }

    /**
     * @return array
     */
    function getNotGroupableFieldNames(): array
    {
        return ['duration', 'created_at', 'average_duration'];
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function followUpFields()
    {
        parent::followUpFields();

        $groupBy = $this->getParam('group_by');

        if ( ! $this->variableHelper->isEmpty($groupBy)) {
            $this->addColumn('average_duration', [
                'header'           => __('Average'),
                'index'            => 'average_duration',
                'type'             => 'number',
                'column_css_class' => 'data-grid-td',
                'filter'           => false
            ]);
        }
    }
}
