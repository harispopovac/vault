const yearDropdownPlugin = function (pluginConfig) {
    var defaultConfig = {
        text: "",
        theme: "light",
        date: new Date(),
        yearStart: 100,
        yearEnd: 2,
    }

    var config = {}
    for (var key in defaultConfig) {
        config[key] =
            pluginConfig && pluginConfig[key] !== undefined
                ? pluginConfig[key]
                : defaultConfig[key]
    }

    var getYear = function (value) {
        var year = new Date(value).getFullYear()
        
        return parseInt(year || new Date().getFullYear())
    }

    var currYear = new Date().getFullYear()
    var selectedYear = getYear(config.date)

    var yearDropdown = document.createElement("select")

    yearDropdown.className = "flatpickr-monthDropdown-months"

    var createSelectElement = function () {
        var start = new Date().getFullYear() - config.yearStart
        var end = currYear + config.yearEnd

        for (var i = end; i >= start; i--) {
            var option = document.createElement("option")
            option.value = i
            option.text = i
            yearDropdown.appendChild(option)
        }
        yearDropdown.value = selectedYear
    }

    return async function (fp) {
        await fp.loaded

        fp.yearSelectContainer = fp.calendarContainer.querySelector(
            ".flatpickr-current-month",
        )

        if (fp.yearSelectContainer) {
            fp.yearSelectContainer.tabIndex = -1
            createSelectElement(selectedYear)
            yearDropdown.addEventListener("change", function (evt) {
                var year = evt.target.options[evt.target.selectedIndex].value
                fp.changeYear(year)
            })

            fp.config.onMonthChange.push(function () {
                yearDropdown.value = fp.currentYear
            })

            fp.yearSelectContainer.append(yearDropdown)
        }

        return {
            onReady: function onReady() {
                var name = fp.monthNav.className

                const yearInputCollection =
                    fp.calendarContainer.getElementsByClassName(name)

                const el = yearInputCollection[0]

                el.parentNode.insertBefore(
                    fp.yearSelectContainer,
                    el.parentNode.firstChild,
                )
            },
        }
    }
}

export default yearDropdownPlugin
