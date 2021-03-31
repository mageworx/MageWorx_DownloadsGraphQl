# MageWorx_DownloadsGraphQl

GraphQL API module for Mageworx [Magento 2 File Downloads & Product attachments](https://www.mageworx.com/magento-2-product-attachments.html) extension. 

## Installation

**1) Copy-to-paste method**
- Download this module and upload it to the `app/code/MageWorx/DownloadsGraphQl` directory *(create "DownloadsGraphQl" first if missing)*

**2) Installation using composer (from packagist)**
- Execute the following command: `composer require mageworx/module-downloads-graph-ql`

## How to use

**1.** ProductInterface describes the possible contents of this object.

It is expanded by MageWorx and attribute "mw_attachments" is added. The following table defines the attributes and objects.

```
icon_type: String. Attachment icon type
icon_type: Int. Attachment ID
name: String. Attachment name
url: String. Attachment URL
size_str: String. Attachment size
downloads_number: Int. Number of downloads
description: String. Attachment description
section_name: String. Section name
section_id: Int. escription: "Section ID
```

**Request:**

```
{
  products(filter: { sku: { eq: "24-MB01" } }) {
    items {
      name
      mw_attachments {
        tab_title
        block_title
        items {         
          icon_type
          id
          name
          url
          size_str
          downloads_number
          description
          section_name
          section_id
        }
      }
    }
  }
}
```

**Response:**

```
{
  "data": {
    "products": {
      "items": [
        {
          "name": "Joust Duffle Bag",
          "mw_attachments": {
            "tab_title": "File Downloads Tab",
            "block_title": "File Downloads Block",
            "items": [
              {
                "icon_type": "pdf",
                "id": 1,
                "name": "test attachment",
                "url": "",
                "size_str": "2.4 MB",
                "downloads_number": 1,
                "description": "Test description 1",
                "section_name": "Default",
                "section_id": 1
              },
              {
                "icon_type": "",
                "id": 2,
                "name": "URL attach",
                "url": "http://exapmle.com",
                "size_str": null,
                "downloads_number": null,
                "description": "Test description 2",
                "section_name": "Test",
                "section_id": 2
              }
            ]
          }
        }
      ]
    }
  }
}
```

**2.** The mwFileDownloads query returns information about the Downloads *(Product Attachments on CMS page, widgets, ect.)*

Query attribute is defined below:

```
attachmentIds: Int. Attachment IDs
sectionIds: Int. Section IDs
```

By default, you can use the following attributes:

```
block_title: String @doc(description: "File Downloads Block Title"
is_group_by_section: Boolean. Indicates whether to group attachments by section
how_to_download_message: String. 'How to download' message
items: [MwAttachment] An array of Attachments

```

**Request:**

```
{
    mwFileDownloads(attachmentIds:[1]){
        block_title
        is_group_by_section
        how_to_download_message
        items {
          icon_type
          id
          name
          url
          size_str
          downloads_number
          description
          section_name
          section_id
        }
    }
}
```

**Response:**

```
{
    "data": {
        "mwFileDownloads": {
            "block_title": "File Downloads Title",
            "is_group_by_section": true,
            "how_to_download_message": "You have to %login% or %register% to download this file",
            "items": [
                {
                    "icon_type": "jpg",
                    "id": 50,
                    "name": "Default Name",
                    "url": "",
                    "size_str": "11.7 KB",
                    "downloads_number": 0,
                    "description": "Default Description",
                    "section_name": "Default",
                    "section_id": 1
                }
            ]
        }
    }
}
```
The same information can be obtained for the Customer group using the authorization token.
