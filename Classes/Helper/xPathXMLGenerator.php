<?php

class xPathXMLGenerator
{

    protected $domDocument = null;

    public function __construct()
    {
        $domDocument = new DOMDocument();
    }

    public function generateNodeFromXPath($xPath)
    {

    }

}

// private XmlNode GenerateNodeFromXPath(string xpath)
//         {
//             XmlDocument doc = new XmlDocument();
//             return GenerateNodeFromXPath(doc, doc as XmlNode, xpath);
//         }

//         private XmlNode GenerateNodeFromXPath(XmlDocument doc, XmlNode parent, string xpath)
//         {
//             // grab the next node name in the xpath; or return parent if empty
//             string[] partsOfXPath = xpath.Trim('/').Split('/');

//             if (partsOfXPath.Length == 0)
//                 return parent;

//             string nextNodeInXPath = partsOfXPath[0];
//             if (string.IsNullOrEmpty(nextNodeInXPath))
//                 return parent;

//             // get or create the node from the name
//             XmlNode node = parent.SelectSingleNode(nextNodeInXPath);
//             if (node == null)
//             {
//                 if (nextNodeInXPath.StartsWith("@"))
//                 {
//                     XmlAttribute anode = doc.CreateAttribute(nextNodeInXPath.Substring(1));
//                     node = parent.Attributes.Append(anode);
//                 }
//                 else
//                     node = parent.AppendChild(doc.CreateElement(nextNodeInXPath));
//             }

//             // rejoin the remainder of the array as an xpath expression and recurse
//             string rest = String.Join("/", partsOfXPath, 1, partsOfXPath.Length - 1);
//             return GenerateNodeFromXPath(doc, node, rest);
//         }

//
