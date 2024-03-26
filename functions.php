<?php 



// Ограничение доступа авторов к постам только к своим
function restrict_authors_to_own_posts( $query ) {
    // Проверяем, что запрос относится к админ-панели и не является запросом AJAX
    if ( is_admin() && !wp_doing_ajax() ) {
        // Получаем текущего пользователя
        $user = wp_get_current_user();

        // Если текущий пользователь не администратор и имеет роль автора
        if ( !current_user_can( 'administrator' ) && in_array( 'author', $user->roles ) ) {
            // Ограничиваем запрос только к постам текущего пользователя
            $query->set( 'author', $user->ID );
        }
    }
}
add_action( 'pre_get_posts', 'restrict_authors_to_own_posts' );

function add_author_to_post_content( $content ) {
    if ( is_single() && in_the_loop() && is_main_query() ) {
        $author = get_the_author();
        $content .= '<p>Author: ' . esc_html( $author ) . '</p>';
    }
    return $content;
}
add_filter( 'the_content', 'add_author_to_post_content' );

// create shortcode for buttons Login/Register/ Logout
function custom_header_login() {
    ob_start(); // Запускаем буферизацию вывода
?>
    <div class="header-login" style="display: flex; gap: 10px;">
        <?php if ( is_user_logged_in() ) : ?>
            <a href="<?php echo wp_logout_url( home_url() ); ?>">Logout</a>
        <?php else : ?>
            <a href="<?php echo wp_login_url( get_permalink() ); ?>">Login</a>
            <a href="<?php echo wp_registration_url(); ?>">Register</a>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean(); // Завершаем буферизацию и возвращаем содержимое
}
add_shortcode( 'custom_header_login', 'custom_header_login' );

// create shortcode for filter
function custom_category_filter_shortcode( $atts ) {
    ob_start();

    // Получаем атрибуты шорткода
    $atts = shortcode_atts(
        array(
            'show_count' => 'true', // Показывать количество постов в каждой категории
        ),
        $atts,
        'custom_category_filter'
    );

    // Получаем список категорий
    $categories = get_categories();

    // Проверяем, была ли выбрана категория
    $selected_cat = get_query_var( 'cat' );

    // Если выбрана "All Categories", перенаправляем на указанную ссылку
    if ( $selected_cat === '0' ) {
        wp_redirect( 'http://localhost:8080' );
        exit;
    }

    // Выводим форму фильтрации
    ?>
    <form id="category-filter-form" action="<?php echo esc_url( home_url() ); ?>" method="get">
        <input type="hidden" name="paged" value="1"> <!-- Добавляем скрытое поле для пагинации -->
        <select name="cat" id="cat">
            <option value="0">All Categories</option>
            <?php foreach ( $categories as $category ) : ?>
                <option value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( $selected_cat, $category->term_id ); ?>>
                    <?php echo esc_html( $category->name ); ?>
                    <?php if ( $atts['show_count'] ) : ?>
                        (<?php echo esc_html( $category->count ); ?>)
                    <?php endif; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" id="apply-filter">Применить</button> <!-- Добавляем кнопку "Применить" -->
    </form>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('apply-filter').addEventListener('click', function(event) {
                event.preventDefault(); // Предотвращаем отправку формы
                document.getElementById('category-filter-form').submit(); // Отправляем форму после нажатия на кнопку "Применить"
            });
        });
    </script>
    <?php

    // Выводим отфильтрованные посты, если выбрана категория
    if ( $selected_cat !== '' && $selected_cat !== 0 && $selected_cat !== '0' ) {
        $args = array(
            'posts_per_page' => -1, // Вывести все посты
            'category'       => $selected_cat, // Фильтруем по текущей категории
        );
        $query = new WP_Query( $args );

        if ( $query->have_posts() ) :
            while ( $query->have_posts() ) : $query->the_post();
                ?>
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <div><?php the_excerpt(); ?></div>
                <?php
            endwhile;
        else :
            echo 'No posts found';
        endif;

        wp_reset_postdata();
    }

    return ob_get_clean();
}
add_shortcode( 'custom_category_filter', 'custom_category_filter_shortcode' );

// Разрешить авторам создавать категории
function allow_authors_to_create_categories() {
    $author = get_role('author');
    $author->add_cap('manage_categories');
}
add_action('init', 'allow_authors_to_create_categories');




