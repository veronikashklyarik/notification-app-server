<script>
    function notificationForm(scheduleType, times, weekDays, everyNDays, cyclicalValue, cyclicalUnit) {
        return {
            scheduleType,
            times,
            weekDays,
            everyNDays,
            cyclicalValue,
            cyclicalUnit,

            addTime() {
                this.times.push('09:00');
            },

            removeTime(index) {
                if (this.times.length > 1) {
                    this.times.splice(index, 1);
                }
            },

            toggleDay(day) {
                const idx = this.weekDays.indexOf(day);
                if (idx > -1) {
                    this.weekDays.splice(idx, 1);
                } else {
                    this.weekDays.push(day);
                }
            },

            isDaySelected(day) {
                return this.weekDays.includes(day);
            },
        };
    }
</script>
