<?php

declare(strict_types=1);

namespace Infrangible\CacheUsage\Model\ResourceModel\FullPageCache;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Infrangible\CacheUsage\Model\FullPageCache;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Collection
    extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(FullPageCache::class, \Infrangible\CacheUsage\Model\ResourceModel\FullPageCache::class);
    }

    /**
     * @param int $days
     *
     * @return void
     */
    public function addCreatedAtLimit(int $days): void
    {
        $date = date('Y-m-d H:i:s', strtotime(sprintf('-%d days', $days)));

        $this->addFieldToFilter('created_at', ['lteq' => $date]);
    }
}
