<?php
namespace App\Form\DataTransformer;

use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use App\Entity\RefOrganisation;

class OrganisationToIdTransformer implements DataTransformerInterface
{
    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @param ObjectManager $om
     */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    /**
     * Transforms an object (issue) to a string (number).
     *
     * @param  Issue|null $value
     * @return string
     */
    public function transform($value)
    {
        if (null === $value) {
            return "";
        }

        return $value->getId();
    }

    /**
     * Transforms a string (number) to an object (issue).
     *
     * @param  string $value
     *
     * @return Issue|null
     *
     * @throws TransformationFailedException if object (issue) is not found.
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        }

        $org = $this->om->getRepository(RefOrganisation::class)->findOneBy(array('id' => $value))
        ;

        if (null === $org) {
            throw new TransformationFailedException(sprintf(
                'L\organisation "%s" n\'existe pas !',
                $value
            ));
        }

        return $org;
    }
}