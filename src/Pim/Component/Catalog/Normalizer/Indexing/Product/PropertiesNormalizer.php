<?php

namespace Pim\Component\Catalog\Normalizer\Indexing\Product;

use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Normalizer\Standard\Product\PropertiesNormalizer as StandardPropertiesNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * Transform the properties of a product object (fields and product values)
 * to the indexing format.
 *
 * @author    Julien Janvier <jjanvier@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PropertiesNormalizer extends SerializerAwareNormalizer implements NormalizerInterface
{
    const FIELD_COMPLETENESS = 'completeness';
    const FIELD_IS_ASSOCIATED = 'is_associated';
    const FIELD_IN_GROUP = 'in_group';

    /**
     * {@inheritdoc}
     */
    public function normalize($product, $format = null, array $context = [])
    {
        if (!$this->serializer instanceof NormalizerInterface) {
            throw new \LogicException('Serializer must be a normalizer');
        }

        $data = [];

        $data[StandardPropertiesNormalizer::FIELD_IDENTIFIER] = $product->getIdentifier();
        $data[StandardPropertiesNormalizer::FIELD_CREATED] = $this->serializer->normalize(
            $product->getCreated(),
            $format
        );
        $data[StandardPropertiesNormalizer::FIELD_UPDATED] = $this->serializer->normalize(
            $product->getUpdated(),
            $format
        );

        $data[StandardPropertiesNormalizer::FIELD_FAMILY] = $this->serializer->normalize(
            $product->getFamily(),
            $format
        );

        $data[StandardPropertiesNormalizer::FIELD_ENABLED] = (bool) $product->isEnabled();
        $data[StandardPropertiesNormalizer::FIELD_CATEGORIES] = $product->getCategoryCodes();

        $groups = array_diff(
            $product->getGroupCodes(),
            null !== $product->getVariantGroup() ? [$product->getVariantGroup()->getCode()] : []
        );

        $data[StandardPropertiesNormalizer::FIELD_GROUPS] = $groups;
        $data[StandardPropertiesNormalizer::FIELD_VARIANT_GROUP] = null !== $product->getVariantGroup()
            ? $product->getVariantGroup()->getCode() : null;

        foreach ($product->getGroupCodes() as $groupCode) {
            $data[self::FIELD_IN_GROUP][$groupCode] = true;
        }

        $data[self::FIELD_IS_ASSOCIATED] = !$product->getAssociations()->isEmpty();

        $data[self::FIELD_COMPLETENESS] = !$product->getCompletenesses()->isEmpty()
            ? $this->serializer->normalize($product->getCompletenesses(), 'indexing', $context) : [];

        $data[StandardPropertiesNormalizer::FIELD_VALUES] = !$product->getValues()->isEmpty()
            ? $this->serializer->normalize($product->getValues(), 'indexing', $context) : [];

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof ProductInterface && 'indexing' === $format;
    }
}
