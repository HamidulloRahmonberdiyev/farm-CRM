<style>
    .label {
        font-size: 23px;
    },
    .order-lable {
        font-size: 50px;
    }
</style>

<div class="label"><strong>Mijoz:</strong> {{ $record->customer->full_name }}</div><br>
<div class="label"><strong>Telefon raqam:</strong> {{ $record->customer->phones->pluck('phone')->implode(', '); }}</div><br><br>
<hr>
<div class="label"><strong>Buyurtma olingan sana:</strong> {{ formatDateHuman($record->date) }}</div><br>
<div class="label"><strong>Oxirgi to'lov qilish sanasi:</strong> {{ formatDateHuman($record->payment_deadline) }}</div><br>
<div class="label"><strong>Eshik soni:</strong> {{ $record->doors_count }}</div><br>
<div class="label"><strong>Deraza soni:</strong> {{ $record->windows_count }}</div><br>

