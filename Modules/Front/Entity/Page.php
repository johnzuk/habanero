<?php
namespace Front\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @ORM\Table(name="page")
 */
class Page
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    protected $content;

    /**
     * @ORM\Column(type="string", length=200)
     * @var string
     */
    protected $title;

    /**
     * @ORM\Column(type="string", length=100)
     * @var string
     */
    protected $slug;

    /**
     * @ORM\Column(type="string", length=100)
     * @var string
     */
    protected $page_name;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getPageName()
    {
        return $this->page_name;
    }

    /**
     * @param string $page_name
     */
    public function setPageName($page_name)
    {
        $this->page_name = $page_name;
    }
}

