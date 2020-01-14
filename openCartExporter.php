<?php

namespace  App\ParseIt\export;

class openCartExporter
{
    private $connection;
    private $webSiteUrl;

    public function __construct(\Doctrine\DBAL\Connection $conn, $webSiteUrl = 'http://allforserver.ru/')
    {
        $this->connection = $conn;
        $this->webSiteUrl = $webSiteUrl;
    }



    public function getProductRow($product_id)
    {
        $sql = "SELECT * FROM oc_product WHERE product_id='{$product_id}'";
        $stmt = $this->connection->query($sql);
        if ($stmt->rowCount())
        {
            $row = $stmt->fetch();
            return $row;
        }
        return false;
    }

    public function getAllProductInfo($row)
    {
        $product = $row;
        $product['attributes'] = $this->getAttributes($row['product_id']);
        $product['gallery'] = $this->getProductImagesByProductId($row['product_id'], $row['image']);
        $product['categories'] = $this->getProductCategory($row['product_id']);
        $product['description'] = $this->getProductDescription($row['product_id']);
        return $product;
    }

    private function getProductDescription($product_id)
    {
        $sql = "SELECT * FROM oc_product_description WHERE product_id='{$product_id}' AND language_id=1";
        $stmt = $this->connection->query($sql);
        $description = '';
        if ($stmt->rowCount())
        {
            $row = $stmt->fetch();
            $description = $row['description'];
        }
        return $description;
    }

    private function getProductCategory($product_id)
    {
        $sql = "SELECT * FROM oc_product_to_category WHERE product_id='{$product_id}'";
        $stmt = $this->connection->query($sql);
        $categories = [];
        if ($stmt->rowCount())
        {
            $row = $stmt->fetch();
            $category_id = $row['category_id'];
            $categories = $this->getCategoriesTree($category_id);
        }
        return $categories;
    }

    private function getCategoriesTree($category_id)
    {
        $sql = "SELECT * FROM oc_category WHERE category_id='{$category_id}'";
        $stmt = $this->connection->query($sql);
        $categories = false;
        if ($stmt->rowCount())
        {
            $row = $stmt->fetch();
            if ($row['parent_id'] != 0)
            {
                $categories = $this->getCategoriesTree($row['parent_id']);
            }
        }
        if ($categoryTitle = $this->getCategoryTitle($category_id))
        {
            $categories[] = $categoryTitle;
        }
        return $categories;
    }

    private function getCategoryTitle($category_id)
    {
        $sql = "SELECT * FROM oc_category_description WHERE category_id='{$category_id}'";
        $stmt = $this->connection->query($sql);
        if ($stmt->rowCount())
        {
            $row = $stmt->fetch();
            return $row['name'];
        }
        return false;
    }

    private function getProductImagesByProductId($product_id, $mainImage = [])
    {
        $sql = "SELECT * FROM oc_product_image WHERE product_id='{$product_id}'";
        $stmt = $this->connection->query($sql);
        $images = [];
        if (!empty($mainImage))
        {
            $images[] = $this->webSiteUrl.'image/'.$mainImage;
        }
        if ($stmt->rowCount())
        {
            $rows = $stmt->fetchAll();
            foreach ($rows as $row)
            {
                $images[] = $this->webSiteUrl.'image/'.$row['image'];
            }
        }
        return $images;
    }

    private function getAttributes($product_id)
    {
        $sql = "SELECT * FROM oc_product_attribute WHERE product_id='{$product_id}'";
        $stmt = $this->connection->query($sql);
        $attr = [];
        if ($stmt->rowCount())
        {
            $rows = $stmt->fetchAll();
            foreach ($rows as $row)
            {
                if ($attrTitle = $this->getAttrTitleById($row['attribute_id']))
                {
                    $attr[$attrTitle] = $row['text'];
                }
            }
        }
        return $attr;
    }

    private function getAttrTitleById($attribute_id)
    {
        $sql = "SELECT * FROM oc_attribute_description WHERE attribute_id='{$attribute_id}'";
        $stmt = $this->connection->query($sql);
        if ($stmt->rowCount())
        {
            $row = $stmt->fetch();
            return $row['name'];
        }
        return false;
    }
}