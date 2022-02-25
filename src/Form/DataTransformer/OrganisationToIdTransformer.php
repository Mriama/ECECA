<?php
namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Doctrine\Common\Persistence\ObjectManager;
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
     * @param  Issue|null $issue
     * @return string
     */
    public function transform($org)
    {
        if (null === $org) {
            return "";
        }

        return $org->getId();
    }

    /**
     * Transforms a string (number) to an object (issue).
     *
     * @param  string $number
     *
     * @return Issue|null
     *
     * @throws TransformationFailedException if object (issue) is not found.
     */
    public function reverseTransform($id)
    {
        if (!$id) {
            return null;
        }

        $org = $this->om->getRepository(RefOrganisation::class)->findOneBy(array('id' => $id))
        ;

        if (null === $org) {
            throw new TransformationFailedException(sprintf(
                'L\organisation "%s" n\'existe pas !',
                $id
            ));
        }

        return $org;
    }
}