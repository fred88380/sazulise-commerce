<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Sazulis | Développeur Web Freelance à Épinal & Vosges</title>
    <meta name="description" content="Création de sites web, e-commerce, applications et SEO sur mesure à Épinal...">

    <?php if (isset($page_canonical)): ?>
        <link rel="canonical" href="<?php echo $page_canonical; ?>" />
    <?php endif; ?>

    <?php if (isset($og_title)): ?>
        <meta property="og:title" content="<?php echo htmlspecialchars($og_title); ?>" />
    <?php endif; ?>
    
    <?php if (isset($og_description)): ?>
        <meta property="og:description" content="<?php echo htmlspecialchars($og_description); ?>" />
    <?php endif; ?>
    
    <meta property="og:type" content="website" />
    <?php if (isset($page_canonical)): ?>
        <meta property="og:url" content="<?php echo $page_canonical; ?>" />
    <?php endif; ?>
    
    <?php if (isset($og_image)): ?>
        <meta property="og:image" content="<?php echo $og_image; ?>" />
    <?php endif; ?>

    </head>