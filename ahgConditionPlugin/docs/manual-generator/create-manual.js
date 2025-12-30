const { Document, Packer, Paragraph, TextRun, HeadingLevel, Table, TableRow, TableCell, 
        WidthType, BorderStyle, AlignmentType, ImageRun, PageBreak, 
        TableOfContents, Header, Footer, PageNumber, NumberFormat } = require('docx');
const fs = require('fs');

async function createManual() {
    const doc = new Document({
        creator: "The Archive and Heritage Group",
        title: "Condition Report Photo Annotation System - User Manual",
        description: "User manual for the AtoM Condition Report Photo Annotation System",
        styles: {
            paragraphStyles: [
                {
                    id: "Normal",
                    name: "Normal",
                    run: { font: "Arial", size: 22 },
                    paragraph: { spacing: { after: 120 } }
                },
                {
                    id: "Heading1",
                    name: "Heading 1",
                    basedOn: "Normal",
                    next: "Normal",
                    run: { font: "Arial", size: 32, bold: true, color: "2E7D32" },
                    paragraph: { spacing: { before: 240, after: 120 } }
                },
                {
                    id: "Heading2",
                    name: "Heading 2",
                    basedOn: "Normal",
                    next: "Normal",
                    run: { font: "Arial", size: 26, bold: true, color: "1565C0" },
                    paragraph: { spacing: { before: 200, after: 100 } }
                },
                {
                    id: "Heading3",
                    name: "Heading 3",
                    basedOn: "Normal",
                    next: "Normal",
                    run: { font: "Arial", size: 24, bold: true, color: "424242" },
                    paragraph: { spacing: { before: 160, after: 80 } }
                }
            ]
        },
        sections: [{
            properties: {
                page: {
                    margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 }
                }
            },
            headers: {
                default: new Header({
                    children: [new Paragraph({
                        children: [
                            new TextRun({ text: "Condition Report Photo Annotation System", font: "Arial", size: 18, color: "666666" })
                        ],
                        alignment: AlignmentType.RIGHT
                    })]
                })
            },
            footers: {
                default: new Footer({
                    children: [new Paragraph({
                        children: [
                            new TextRun({ text: "Page ", font: "Arial", size: 18 }),
                            new TextRun({ children: [PageNumber.CURRENT], font: "Arial", size: 18 }),
                            new TextRun({ text: " of ", font: "Arial", size: 18 }),
                            new TextRun({ children: [PageNumber.TOTAL_PAGES], font: "Arial", size: 18 })
                        ],
                        alignment: AlignmentType.CENTER
                    })]
                })
            },
            children: [
                // Title Page
                new Paragraph({ text: "", spacing: { after: 1000 } }),
                new Paragraph({
                    children: [new TextRun({ text: "CONDITION REPORT", font: "Arial", size: 56, bold: true, color: "2E7D32" })],
                    alignment: AlignmentType.CENTER
                }),
                new Paragraph({
                    children: [new TextRun({ text: "PHOTO ANNOTATION SYSTEM", font: "Arial", size: 48, bold: true, color: "1565C0" })],
                    alignment: AlignmentType.CENTER,
                    spacing: { after: 400 }
                }),
                new Paragraph({
                    children: [new TextRun({ text: "User Manual", font: "Arial", size: 36, italics: true, color: "666666" })],
                    alignment: AlignmentType.CENTER,
                    spacing: { after: 800 }
                }),
                new Paragraph({
                    children: [new TextRun({ text: "Version 1.0", font: "Arial", size: 24 })],
                    alignment: AlignmentType.CENTER
                }),
                new Paragraph({
                    children: [new TextRun({ text: "December 2025", font: "Arial", size: 24 })],
                    alignment: AlignmentType.CENTER,
                    spacing: { after: 600 }
                }),
                new Paragraph({
                    children: [new TextRun({ text: "Spectrum 5.0 Compliant", font: "Arial", size: 22, bold: true, color: "7B1FA2" })],
                    alignment: AlignmentType.CENTER,
                    spacing: { after: 1200 }
                }),
                new Paragraph({
                    children: [new TextRun({ text: "The Archive and Heritage Group", font: "Arial", size: 22 })],
                    alignment: AlignmentType.CENTER
                }),
                new Paragraph({
                    children: [new TextRun({ text: "www.theahg.co.za", font: "Arial", size: 20, color: "1565C0" })],
                    alignment: AlignmentType.CENTER
                }),
                
                new Paragraph({ children: [new PageBreak()] }),
                
                // Table of Contents
                new Paragraph({ text: "Table of Contents", heading: HeadingLevel.HEADING_1 }),
                new Paragraph({ text: "1. Introduction", style: "Normal" }),
                new Paragraph({ text: "2. Getting Started", style: "Normal" }),
                new Paragraph({ text: "3. Photo Gallery", style: "Normal" }),
                new Paragraph({ text: "4. Using the Annotation Editor", style: "Normal" }),
                new Paragraph({ text: "5. Annotation Tools Reference", style: "Normal" }),
                new Paragraph({ text: "6. Damage Categories", style: "Normal" }),
                new Paragraph({ text: "7. Saving and Managing Annotations", style: "Normal" }),
                new Paragraph({ text: "8. Exporting Reports", style: "Normal" }),
                new Paragraph({ text: "9. AI Detection Integration", style: "Normal" }),
                new Paragraph({ text: "10. Keyboard Shortcuts", style: "Normal" }),
                new Paragraph({ text: "11. Troubleshooting", style: "Normal" }),
                
                new Paragraph({ children: [new PageBreak()] }),
                
                // Section 1: Introduction
                new Paragraph({ text: "1. Introduction", heading: HeadingLevel.HEADING_1 }),
                new Paragraph({
                    children: [new TextRun({ 
                        text: "The Condition Report Photo Annotation System is a comprehensive tool for documenting the physical condition of museum objects, archival materials, and heritage items. It integrates with the AtoM (Access to Memory) archival management system and follows Spectrum 5.0 standards for condition checking and technical assessment.",
                        font: "Arial", size: 22
                    })]
                }),
                
                new Paragraph({ text: "1.1 Key Features", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({ text: "• Interactive canvas-based image annotation using Fabric.js", style: "Normal" }),
                new Paragraph({ text: "• Multiple drawing tools: rectangles, circles, arrows, freehand, text, and markers", style: "Normal" }),
                new Paragraph({ text: "• Category-based color coding for different damage types", style: "Normal" }),
                new Paragraph({ text: "• Save annotations as JSON for future editing", style: "Normal" }),
                new Paragraph({ text: "• Toggle annotation visibility on/off", style: "Normal" }),
                new Paragraph({ text: "• Export printable condition reports", style: "Normal" }),
                new Paragraph({ text: "• AI detection integration ready for automated damage detection", style: "Normal" }),
                new Paragraph({ text: "• Full audit trail of all annotation changes", style: "Normal" }),
                
                new Paragraph({ text: "1.2 Spectrum 5.0 Compliance", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({
                    children: [new TextRun({ 
                        text: "This system supports the Spectrum 5.0 Condition checking and technical assessment procedure, enabling museums to:",
                        font: "Arial", size: 22
                    })]
                }),
                new Paragraph({ text: "• Document object condition with photographic evidence", style: "Normal" }),
                new Paragraph({ text: "• Record specific damage locations on images", style: "Normal" }),
                new Paragraph({ text: "• Track condition changes over time", style: "Normal" }),
                new Paragraph({ text: "• Generate condition reports for loans and exhibitions", style: "Normal" }),
                new Paragraph({ text: "• Support conservation planning and prioritization", style: "Normal" }),
                
                new Paragraph({ children: [new PageBreak()] }),
                
                // Section 2: Getting Started
                new Paragraph({ text: "2. Getting Started", heading: HeadingLevel.HEADING_1 }),
                
                new Paragraph({ text: "2.1 Accessing Condition Reports", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({ text: "There are several ways to access the condition report system:", style: "Normal" }),
                new Paragraph({ text: "1. From the Information Object View:", style: "Normal" }),
                new Paragraph({ text: "   • Navigate to any archival description or museum object", style: "Normal" }),
                new Paragraph({ text: "   • In the sidebar under 'Explore', click 'Condition Report'", style: "Normal" }),
                new Paragraph({ text: "2. Direct URL Access:", style: "Normal" }),
                new Paragraph({ text: "   • Gallery: /condition/check/{check_id}/photos", style: "Normal" }),
                new Paragraph({ text: "   • Annotate: /condition/photo/{photo_id}/annotate", style: "Normal" }),
                new Paragraph({ text: "3. From Spectrum Module:", style: "Normal" }),
                new Paragraph({ text: "   • Access through the Spectrum dashboard condition checks", style: "Normal" }),
                
                new Paragraph({ text: "2.2 Required Permissions", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({ text: "• View Photos: Any user with read access to the object", style: "Normal" }),
                new Paragraph({ text: "• Add/Edit Annotations: Editor role or object update permission", style: "Normal" }),
                new Paragraph({ text: "• Upload Photos: Editor role or object update permission", style: "Normal" }),
                new Paragraph({ text: "• Delete Photos: Editor role or object update permission", style: "Normal" }),
                
                new Paragraph({ children: [new PageBreak()] }),
                
                // Section 3: Photo Gallery
                new Paragraph({ text: "3. Photo Gallery", heading: HeadingLevel.HEADING_1 }),
                new Paragraph({
                    children: [new TextRun({ 
                        text: "The photo gallery displays all condition photos for a specific condition check, organized in a grid layout.",
                        font: "Arial", size: 22
                    })]
                }),
                
                new Paragraph({ text: "3.1 Gallery Features", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({ text: "• Thumbnail grid view with photo type badges", style: "Normal" }),
                new Paragraph({ text: "• Annotation count indicator (blue badge with number)", style: "Normal" }),
                new Paragraph({ text: "• AI detection indicator (pink 'AI' badge)", style: "Normal" }),
                new Paragraph({ text: "• Photo type labels (General, Detail, Damage, Before, After, etc.)", style: "Normal" }),
                new Paragraph({ text: "• Quick actions: Annotate, View Full Size, Delete", style: "Normal" }),
                
                new Paragraph({ text: "3.2 Uploading Photos", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({ text: "To upload new condition photos:", style: "Normal" }),
                new Paragraph({ text: "1. Click the 'Upload Photos' button in the gallery", style: "Normal" }),
                new Paragraph({ text: "2. Select photo type from dropdown (General, Damage, Detail, etc.)", style: "Normal" }),
                new Paragraph({ text: "3. Optionally enter a caption", style: "Normal" }),
                new Paragraph({ text: "4. Drag and drop an image or click to browse", style: "Normal" }),
                new Paragraph({ text: "5. Click 'Upload' to save", style: "Normal" }),
                new Paragraph({ text: "", spacing: { after: 100 } }),
                new Paragraph({
                    children: [new TextRun({ 
                        text: "Supported formats: JPEG, PNG, GIF, WebP (max 20MB)",
                        font: "Arial", size: 22, italics: true, color: "666666"
                    })]
                }),
                
                new Paragraph({ text: "3.3 Photo Types", heading: HeadingLevel.HEADING_2 }),
                
                // Photo types table
                new Table({
                    width: { size: 100, type: WidthType.PERCENTAGE },
                    rows: [
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Type", bold: true, font: "Arial", size: 20 })] })], shading: { fill: "E8F5E9" } }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Description", bold: true, font: "Arial", size: 20 })] })], shading: { fill: "E8F5E9" } }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Use Case", bold: true, font: "Arial", size: 20 })] })], shading: { fill: "E8F5E9" } })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "General", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Overall view of the object", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Standard documentation", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Detail", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Close-up of specific areas", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Signatures, marks, details", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Damage", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Documentation of damage", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Cracks, tears, stains, loss", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Before", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Pre-treatment condition", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Conservation documentation", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "After", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Post-treatment condition", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Conservation documentation", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Raking", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Raking light photography", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Surface texture, undulations", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "UV", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Ultraviolet light", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Retouching, varnish, repairs", font: "Arial", size: 20 })] })] })
                            ]
                        })
                    ]
                }),
                
                new Paragraph({ children: [new PageBreak()] }),
                
                // Section 4: Annotation Editor
                new Paragraph({ text: "4. Using the Annotation Editor", heading: HeadingLevel.HEADING_1 }),
                new Paragraph({
                    children: [new TextRun({ 
                        text: "The annotation editor provides a canvas-based interface for marking and documenting condition issues directly on photographs.",
                        font: "Arial", size: 22
                    })]
                }),
                
                new Paragraph({ text: "4.1 Editor Interface", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({ text: "The editor consists of:", style: "Normal" }),
                new Paragraph({ text: "• Toolbar: Drawing tools, category selection, color picker, actions", style: "Normal" }),
                new Paragraph({ text: "• Canvas: The photo with annotation overlay", style: "Normal" }),
                new Paragraph({ text: "• Status Bar: Current tool, annotation count", style: "Normal" }),
                new Paragraph({ text: "• Side Panel: Annotation list, photo details, help", style: "Normal" }),
                
                new Paragraph({ text: "4.2 Creating Annotations", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({ text: "To create an annotation:", style: "Normal" }),
                new Paragraph({ text: "1. Select a tool from the toolbar (rectangle, circle, arrow, etc.)", style: "Normal" }),
                new Paragraph({ text: "2. Choose a damage category from the dropdown", style: "Normal" }),
                new Paragraph({ text: "3. Click and drag on the image to draw the shape", style: "Normal" }),
                new Paragraph({ text: "4. A prompt will appear to add notes for the annotation", style: "Normal" }),
                new Paragraph({ text: "5. Click 'Save' to store all annotations", style: "Normal" }),
                
                new Paragraph({ text: "4.3 Editing Annotations", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({ text: "• Select: Click on any annotation to select it", style: "Normal" }),
                new Paragraph({ text: "• Move: Drag selected annotation to reposition", style: "Normal" }),
                new Paragraph({ text: "• Resize: Use corner handles to resize shapes", style: "Normal" }),
                new Paragraph({ text: "• Delete: Press Delete key or click trash button", style: "Normal" }),
                new Paragraph({ text: "• Undo: Press Ctrl+Z or click undo button", style: "Normal" }),
                
                new Paragraph({ children: [new PageBreak()] }),
                
                // Section 5: Tools Reference
                new Paragraph({ text: "5. Annotation Tools Reference", heading: HeadingLevel.HEADING_1 }),
                
                new Table({
                    width: { size: 100, type: WidthType.PERCENTAGE },
                    rows: [
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Tool", bold: true, font: "Arial", size: 20 })] })], shading: { fill: "E3F2FD" }, width: { size: 15, type: WidthType.PERCENTAGE } }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Icon", bold: true, font: "Arial", size: 20 })] })], shading: { fill: "E3F2FD" }, width: { size: 10, type: WidthType.PERCENTAGE } }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Description", bold: true, font: "Arial", size: 20 })] })], shading: { fill: "E3F2FD" } }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Best For", bold: true, font: "Arial", size: 20 })] })], shading: { fill: "E3F2FD" } })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Select", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "🖱️", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Select and move annotations", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Editing existing annotations", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Rectangle", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "▢", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Draw rectangular area", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Square/rectangular damage", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Circle/Ellipse", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "○", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Draw elliptical area", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Spots, stains, circular damage", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Arrow", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "→", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Draw pointing arrow", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Pointing to specific details", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Freehand", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "✏️", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Draw freehand lines", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Irregular shapes, cracks", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Text", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "A", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Add text label", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Adding descriptions on image", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Marker", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "📍", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Add numbered point marker", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Referencing specific points", font: "Arial", size: 20 })] })] })
                            ]
                        })
                    ]
                }),
                
                new Paragraph({ children: [new PageBreak()] }),
                
                // Section 6: Damage Categories
                new Paragraph({ text: "6. Damage Categories", heading: HeadingLevel.HEADING_1 }),
                new Paragraph({
                    children: [new TextRun({ 
                        text: "The system uses color-coded categories to classify different types of damage and notes. Selecting a category automatically sets the annotation color.",
                        font: "Arial", size: 22
                    })]
                }),
                
                new Table({
                    width: { size: 100, type: WidthType.PERCENTAGE },
                    rows: [
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Category", bold: true, font: "Arial", size: 20 })] })], shading: { fill: "FFEBEE" } }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Color", bold: true, font: "Arial", size: 20 })] })], shading: { fill: "FFEBEE" } }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Hex Code", bold: true, font: "Arial", size: 20 })] })], shading: { fill: "FFEBEE" } }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Description", bold: true, font: "Arial", size: 20 })] })], shading: { fill: "FFEBEE" } })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Damage", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Red", font: "Arial", size: 20, color: "FF0000" })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "#FF0000", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "General damage", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Crack", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Orange Red", font: "Arial", size: 20, color: "FF4500" })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "#FF4500", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Cracks, fractures", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Stain", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Goldenrod", font: "Arial", size: 20, color: "DAA520" })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "#DAA520", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Stains, discoloration", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Tear", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Crimson", font: "Arial", size: 20, color: "DC143C" })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "#DC143C", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Tears, rips", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Loss", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Dark Violet", font: "Arial", size: 20, color: "9400D3" })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "#9400D3", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Missing material", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Mould", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Dark Red", font: "Arial", size: 20, color: "8B0000" })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "#8B0000", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Mould, fungal growth", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Pest", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Dark Green", font: "Arial", size: 20, color: "006400" })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "#006400", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Insect damage", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Water", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Dodger Blue", font: "Arial", size: 20, color: "1E90FF" })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "#1E90FF", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Water damage, tide lines", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Note", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Royal Blue", font: "Arial", size: 20, color: "4169E1" })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "#4169E1", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "General notes", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "AI Detected", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Deep Pink", font: "Arial", size: 20, color: "FF1493" })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "#FF1493", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "AI-detected anomalies", font: "Arial", size: 20 })] })] })
                            ]
                        })
                    ]
                }),
                
                new Paragraph({ children: [new PageBreak()] }),
                
                // Section 7: Saving
                new Paragraph({ text: "7. Saving and Managing Annotations", heading: HeadingLevel.HEADING_1 }),
                
                new Paragraph({ text: "7.1 Saving Annotations", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({ text: "• Annotations are NOT automatically saved", style: "Normal" }),
                new Paragraph({ text: "• Click the 'Save' button (or Ctrl+S) to save all annotations", style: "Normal" }),
                new Paragraph({ text: "• The Save button turns yellow when there are unsaved changes", style: "Normal" }),
                new Paragraph({ text: "• A confirmation message appears when saved successfully", style: "Normal" }),
                
                new Paragraph({ text: "7.2 Annotation Storage", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({ text: "Annotations are stored as JSON data in the database, including:", style: "Normal" }),
                new Paragraph({ text: "• Unique annotation ID", style: "Normal" }),
                new Paragraph({ text: "• Shape type and coordinates", style: "Normal" }),
                new Paragraph({ text: "• Category and color", style: "Normal" }),
                new Paragraph({ text: "• User notes", style: "Normal" }),
                new Paragraph({ text: "• Creation timestamp", style: "Normal" }),
                new Paragraph({ text: "• Creator user ID", style: "Normal" }),
                new Paragraph({ text: "• AI-generated flag (if applicable)", style: "Normal" }),
                
                new Paragraph({ text: "7.3 Viewing Annotations", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({ text: "• Toggle visibility: Click the eye icon to show/hide annotations", style: "Normal" }),
                new Paragraph({ text: "• Annotation list: View all annotations in the side panel", style: "Normal" }),
                new Paragraph({ text: "• Gallery badges: See annotation count on photo thumbnails", style: "Normal" }),
                
                new Paragraph({ children: [new PageBreak()] }),
                
                // Section 8: Exporting
                new Paragraph({ text: "8. Exporting Reports", heading: HeadingLevel.HEADING_1 }),
                
                new Paragraph({ text: "8.1 Export Options", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({ text: "The system provides an HTML export that can be printed or saved as PDF:", style: "Normal" }),
                new Paragraph({ text: "1. From the photo gallery, click 'Export Report'", style: "Normal" }),
                new Paragraph({ text: "2. A printable HTML report opens in a new tab", style: "Normal" }),
                new Paragraph({ text: "3. Use browser print (Ctrl+P) to print or save as PDF", style: "Normal" }),
                
                new Paragraph({ text: "8.2 Report Contents", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({ text: "The exported report includes:", style: "Normal" }),
                new Paragraph({ text: "• Condition check reference and date", style: "Normal" }),
                new Paragraph({ text: "• Object identifier and title", style: "Normal" }),
                new Paragraph({ text: "• Overall condition rating", style: "Normal" }),
                new Paragraph({ text: "• Condition notes and recommendations", style: "Normal" }),
                new Paragraph({ text: "• All photos with their annotations listed", style: "Normal" }),
                new Paragraph({ text: "• Annotation statistics", style: "Normal" }),
                
                new Paragraph({ children: [new PageBreak()] }),
                
                // Section 9: AI Integration
                new Paragraph({ text: "9. AI Detection Integration", heading: HeadingLevel.HEADING_1 }),
                new Paragraph({
                    children: [new TextRun({ 
                        text: "The system is designed to integrate with AI-based damage detection systems, particularly for mould detection.",
                        font: "Arial", size: 22
                    })]
                }),
                
                new Paragraph({ text: "9.1 How AI Detection Works", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({ text: "1. An external application captures photos", style: "Normal" }),
                new Paragraph({ text: "2. AI model analyzes the image for anomalies", style: "Normal" }),
                new Paragraph({ text: "3. Detection results are sent to the API", style: "Normal" }),
                new Paragraph({ text: "4. System creates annotations from detections", style: "Normal" }),
                new Paragraph({ text: "5. AI annotations appear with dashed borders and pink color", style: "Normal" }),
                
                new Paragraph({ text: "9.2 AI Annotation Format", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({ text: "AI detections include:", style: "Normal" }),
                new Paragraph({ text: "• Bounding box coordinates", style: "Normal" }),
                new Paragraph({ text: "• Detection category (mould, crack, etc.)", style: "Normal" }),
                new Paragraph({ text: "• Confidence score (0-100%)", style: "Normal" }),
                new Paragraph({ text: "• AI-generated flag for identification", style: "Normal" }),
                
                new Paragraph({ text: "9.3 API Endpoint", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({
                    children: [new TextRun({ 
                        text: "POST /condition/annotation/save",
                        font: "Courier New", size: 20, bold: true
                    })]
                }),
                new Paragraph({ text: "Content-Type: application/json", style: "Normal" }),
                new Paragraph({ text: "", spacing: { after: 100 } }),
                new Paragraph({
                    children: [new TextRun({ 
                        text: "See technical documentation for full API specification.",
                        font: "Arial", size: 22, italics: true
                    })]
                }),
                
                new Paragraph({ children: [new PageBreak()] }),
                
                // Section 10: Keyboard Shortcuts
                new Paragraph({ text: "10. Keyboard Shortcuts", heading: HeadingLevel.HEADING_1 }),
                
                new Table({
                    width: { size: 100, type: WidthType.PERCENTAGE },
                    rows: [
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Shortcut", bold: true, font: "Arial", size: 20 })] })], shading: { fill: "FFF3E0" }, width: { size: 30, type: WidthType.PERCENTAGE } }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Action", bold: true, font: "Arial", size: 20 })] })], shading: { fill: "FFF3E0" } })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Delete / Backspace", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Delete selected annotation", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Ctrl + Z", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Undo last action", font: "Arial", size: 20 })] })] })
                            ]
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Ctrl + S", font: "Arial", size: 20 })] })] }),
                                new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: "Save annotations", font: "Arial", size: 20 })] })] })
                            ]
                        })
                    ]
                }),
                
                new Paragraph({ children: [new PageBreak()] }),
                
                // Section 11: Troubleshooting
                new Paragraph({ text: "11. Troubleshooting", heading: HeadingLevel.HEADING_1 }),
                
                new Paragraph({ text: "11.1 Common Issues", heading: HeadingLevel.HEADING_2 }),
                
                new Paragraph({
                    children: [new TextRun({ text: "Image not loading:", font: "Arial", size: 22, bold: true })]
                }),
                new Paragraph({ text: "• Check that the image file exists in /uploads/condition_photos/", style: "Normal" }),
                new Paragraph({ text: "• Verify file permissions (should be readable by www-data)", style: "Normal" }),
                new Paragraph({ text: "• Check browser console for CORS or loading errors", style: "Normal" }),
                
                new Paragraph({
                    children: [new TextRun({ text: "Annotations not saving:", font: "Arial", size: 22, bold: true })]
                }),
                new Paragraph({ text: "• Ensure you are logged in with edit permissions", style: "Normal" }),
                new Paragraph({ text: "• Check for JavaScript errors in browser console", style: "Normal" }),
                new Paragraph({ text: "• Verify database connectivity", style: "Normal" }),
                
                new Paragraph({
                    children: [new TextRun({ text: "Annotations not displaying:", font: "Arial", size: 22, bold: true })]
                }),
                new Paragraph({ text: "• Click the eye icon to ensure visibility is on", style: "Normal" }),
                new Paragraph({ text: "• Refresh the page to reload from database", style: "Normal" }),
                new Paragraph({ text: "• Check that annotations were saved (Save button should be blue)", style: "Normal" }),
                
                new Paragraph({ text: "11.2 Getting Help", heading: HeadingLevel.HEADING_2 }),
                new Paragraph({ text: "For technical support:", style: "Normal" }),
                new Paragraph({ text: "• Check log files: /var/log/atom/condition.log", style: "Normal" }),
                new Paragraph({ text: "• Contact: support@theahg.co.za", style: "Normal" }),
                new Paragraph({ text: "• Documentation: https://theahg.co.za/docs", style: "Normal" }),
                
                new Paragraph({ text: "", spacing: { after: 400 } }),
                new Paragraph({
                    children: [new TextRun({ 
                        text: "— End of Manual —",
                        font: "Arial", size: 22, italics: true, color: "666666"
                    })],
                    alignment: AlignmentType.CENTER
                })
            ]
        }]
    });

    const buffer = await Packer.toBuffer(doc);
    fs.writeFileSync('/mnt/user-data/outputs/Condition-Annotation-User-Manual.docx', buffer);
    console.log('Manual created: /mnt/user-data/outputs/Condition-Annotation-User-Manual.docx');
}

createManual().catch(console.error);
