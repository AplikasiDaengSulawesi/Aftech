allprojects {
    repositories {
        google()
        mavenCentral()
    }
}

// Perbaikan sintaks buildDirectory untuk Gradle terbaru
rootProject.layout.buildDirectory.set(rootProject.file("${rootProject.projectDir}/../build"))

subprojects {
    project.layout.buildDirectory.set(rootProject.layout.buildDirectory.dir(project.name))
}

subprojects {
    afterEvaluate {
        if (project.hasProperty("android")) {
            val android = project.extensions.findByName("android") as? com.android.build.gradle.BaseExtension
            android?.let {
                // Otomatis menyuntikkan namespace jika library (seperti blue_thermal_printer) tidak memilikinya
                if (it.namespace == null) {
                    it.namespace = "id.base.${project.name.replace("-", "_")}"
                }
            }
        }
    }
}

tasks.register<Delete>("clean") {
    delete(rootProject.layout.buildDirectory)
}
