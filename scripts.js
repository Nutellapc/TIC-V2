document.addEventListener("DOMContentLoaded", () => {
    // Obtener datos pasados desde el servidor
    const { user_roles, unique_roles, unique_categories, courses_count, users, courses } = dashboardData;

    // Parsear datos si es necesario
    const rolesData = user_roles;
    const uniqueRoles = unique_roles;
    const uniqueCategories = unique_categories;
    const coursesCount = courses_count;
    const parsedUsers = users;
    const parsedCourses = courses;

    // Inicializar filtros y gráficos
    setupFilters(uniqueRoles, rolesData, parsedUsers, uniqueCategories, parsedCourses);
    setupCharts(rolesData, coursesCount);
});

function setupFilters(uniqueRoles, rolesData, users, uniqueCategories, courses) {
    // Configurar filtro de roles
    const roleFilter = document.getElementById("role-filter");
    const userList = document.getElementById("user-list");
    uniqueRoles.forEach(role => {
        const option = document.createElement("option");
        option.value = role;
        option.textContent = role;
        roleFilter.appendChild(option);
    });

    roleFilter.addEventListener("change", () => {
        const selectedRole = roleFilter.value;
        userList.innerHTML = "<h2>Lista de Usuarios</h2>";
        users.forEach(user => {
            if (user.role_name === selectedRole || selectedRole === "all") {
                userList.innerHTML += `<p>ID: ${user.id}, Nombre: ${user.firstname} ${user.lastname}, Usuario: ${user.username}, Rol: ${user.role_name}</p>`;
            }
        });
    });

    // Configurar filtro de categorías
    const categoryFilter = document.getElementById("category-filter");
    const courseList = document.getElementById("course-list");
    uniqueCategories.forEach(category => {
        const option = document.createElement("option");
        option.value = category;
        option.textContent = category;
        categoryFilter.appendChild(option);
    });

    categoryFilter.addEventListener("change", () => {
        const selectedCategory = categoryFilter.value;
        courseList.innerHTML = "<h2>Lista de Cursos</h2>";
        courses.forEach(course => {
            if (course.category_name === selectedCategory || selectedCategory === "all") {
                courseList.innerHTML += `<p>ID: ${course.id}, Nombre: ${course.fullname}, Categoría: ${course.category_name}</p>`;
            }
        });
    });
}

function setupCharts(rolesData, coursesCount) {
    // Crear gráfico de usuarios por rol
    const usersChartCtx = document.getElementById("usersChart").getContext("2d");
    new Chart(usersChartCtx, {
        type: "pie",
        data: {
            labels: Object.keys(rolesData),
            datasets: [{
                label: "Usuarios por Rol",
                data: Object.values(rolesData),
                backgroundColor: ["#FF6384", "#36A2EB", "#FFCE56", "#4BC0C0"],
            }]
        }
    });

    // Crear gráfico de cantidad de cursos
    const coursesChartCtx = document.getElementById("coursesChart").getContext("2d");
    new Chart(coursesChartCtx, {
        type: "bar",
        data: {
            labels: ["Total de Cursos"],
            datasets: [{
                label: "Cantidad de Cursos",
                data: [coursesCount],
                backgroundColor: "#36A2EB",
            }]
        }
    });
}
