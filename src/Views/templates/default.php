<?php
    /**
     * @var array{
     *   title: string,
     *   content: string,
     *   scripts?: string
     * } $data
     */
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title><?php echo $data['title'] ?></title>
        <!-- <link rel="shortcut icon" href="./assets/img/liste-de-taches.png" author="smashingstocks" /> -->
        <link rel="stylesheet" href="/public/assets/css/style.css" />
    </head>
    <body class="bg-(--bg-primary) font-[Roboto] text-base text-(--font-color)">
        <header>
            <nav
                class="flex h-20 items-center justify-between bg-(--bg-secondary) p-4"
            >
                <a
                    href="index"
                    class="text(--font-color) block cursor-pointer text-center no-underline"
                >
                    <img
                        src="/public/assets/img/Logo.webp"
                        alt="Accueil"
                        id="logo"
                        class="h-9"
                    />
                </a>
                <div
                    class="cursor-pointer text-2xl text-white transition-transform duration-500 md:hidden"
                    id="menu-toggle"
                >
                    <div
                        class="mb-1 h-1 w-6 bg-white transition-transform"
                        id="bar1"
                    ></div>
                    <div
                        class="mb-1 h-1 w-6 bg-white transition-opacity"
                        id="bar2"
                    ></div>
                    <div
                        class="h-1 w-6 bg-white transition-transform"
                        id="bar3"
                    ></div>
                </div>
                <ul
                    class="absolute top-[80px] left-0 z-50 hidden w-full flex-col items-center bg-(--bg-secondary) md:static md:flex md:flex-row"
                    id="menu"
                >
                    <li class="px-0 py-2.5 text-center md:flex-1">
                        <a href="characters-gallery" class="hover:text-gray-400"
                            >Galerie de personnages</a
                        >
                    </li>
                    <li class="px-0 py-2.5 text-center md:flex-1">
                        <a href="weapons-gallery" class="hover:text-gray-400"
                            >Galerie d'armes</a
                        >
                    </li>
                    <li class="px-0 py-2.5 text-center md:flex-1">
                        <a href="artifacts-gallery" class="hover:text-gray-400"
                            >Galerie d'artéfacts</a
                        >
                    </li>
                    <li class="px-0 py-2.5 text-center md:flex-1">
                        <a href="teams-gallery" class="hover:text-gray-400"
                            >Galerie de teams</a
                        >
                    </li>
                    <?php if (isset($_SESSION['user'])): ?><?php if
    (isset($_SESSION['id_role']) && $_SESSION['id_role'] == 2):
?>
                    <li class="px-0 py-2.5 text-center md:flex-1">
                        <a href="teams" class="hover:text-gray-400"
                            >Mes Teams</a
                        >
                    </li>
                    <?php elseif (isset($_SESSION['id_role']) &&
                        $_SESSION['id_role'] == 1): ?>
                    <li class="px-0 py-2.5 text-center md:flex-1">
                        <a href="admin" class="hover:text-gray-400"
                            >Panneau admin</a
                        >
                    </li>
                    <?php endif; ?>
                    <li class="px-0 py-2.5 text-center md:flex-1">
                        <a href="logout" class="hover:text-gray-400"
                            >Se déconnecter</a
                        >
                    </li>
                    <?php else: ?>
                    <li class="px-0 py-2.5 text-center md:flex-1">
                        <a href="login" class="hover:text-gray-400"
                            >Se connecter</a
                        >
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>
        <main
            class="flex min-h-(--main-min-height) flex-col items-center justify-evenly"
        >
            <?php echo $data['content'] ?>
        </main>
        <footer class="flex h-10 items-center bg-(--bg-secondary) text-sm">
            <a
                href="#"
                class="text(--font-color) block w-full cursor-pointer text-center hover:text-gray-400"
                >Mentions légales</a
            >
        </footer>
        <script src="<?php echo BASE_URL . '/public/assets/js/menu.js' ?>"></script>
         <?php if (isset($data['scripts'])) {
                 echo $data['scripts'];
             }
         ?>
    </body>
</html>
