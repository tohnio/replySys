package com.example.replyapp.ui

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.TextFieldValue
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.replyapp.api.OrdemServico
import com.example.replyapp.api.OsRequest
import com.example.replyapp.api.RetrofitClient
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

fun formatPhone(digits: String): String {
    return when {
        digits.length <= 2 -> digits
        digits.length <= 6 -> "(${digits.substring(0, 2)}) ${digits.substring(2)}"
        digits.length <= 10 -> "(${digits.substring(0, 2)}) ${digits.substring(2, 6)}-${digits.substring(6)}"
        else -> "(${digits.substring(0, 2)}) ${digits.substring(2, 7)}-${digits.substring(7, minOf(digits.length, 11))}"
    }
}

fun calculateDateFromDayOfWeek(selection: String): String {
    val calendar = java.util.Calendar.getInstance()
    val todayDayOfWeek = calendar.get(java.util.Calendar.DAY_OF_WEEK)
    
    val targetDayOfWeek = when (selection) {
        "Domingo" -> java.util.Calendar.SUNDAY
        "Segunda-feira" -> java.util.Calendar.MONDAY
        "Terça-feira" -> java.util.Calendar.TUESDAY
        "Quarta-feira" -> java.util.Calendar.WEDNESDAY
        "Quinta-feira" -> java.util.Calendar.THURSDAY
        "Sexta-feira" -> java.util.Calendar.FRIDAY
        "Sábado" -> java.util.Calendar.SATURDAY
        else -> -1
    }
    
    if (targetDayOfWeek == -1) {
        calendar.add(java.util.Calendar.DAY_OF_YEAR, 7)
    } else {
        var daysDiff = targetDayOfWeek - todayDayOfWeek
        if (daysDiff <= 0) {
            daysDiff += 7
        }
        calendar.add(java.util.Calendar.DAY_OF_YEAR, daysDiff)
    }
    
    return java.text.SimpleDateFormat("yyyy-MM-dd", java.util.Locale.US).format(calendar.time)
}

class CreateOsViewModel : ViewModel() {
    fun saveOs(request: OsRequest, onSuccess: () -> Unit) {
        viewModelScope.launch {
            try {
                RetrofitClient.instance.createOrdemServico(request)
                onSuccess()
            } catch (e: Exception) {
                e.printStackTrace()
            }
        }
    }

    fun updateOs(id: Int, request: OsRequest, onSuccess: () -> Unit) {
        viewModelScope.launch {
            try {
                RetrofitClient.instance.updateOrdemServico(id, request)
                onSuccess()
            } catch (e: Exception) {
                e.printStackTrace()
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CreateOsScreen(
    osToEdit: OrdemServico?,
    onBack: () -> Unit,
    viewModel: CreateOsViewModel = viewModel()
) {
    var nome by remember { mutableStateOf(osToEdit?.cliente?.nome ?: "") }
    var telefone by remember { mutableStateOf(TextFieldValue(
        text = osToEdit?.cliente?.telefone?.filter { it.isDigit() }?.let { formatPhone(it) } ?: ""
    )) }
    var modelo by remember { mutableStateOf(osToEdit?.modelo ?: "") }
    var descricaoReparo by remember { mutableStateOf(osToEdit?.defeito_relatado ?: "") }

    val today = SimpleDateFormat("dd/MM/yyyy", Locale("pt", "BR")).format(Date())
    val entryDate = osToEdit?.data_entrada?.let { entryStr ->
        try {
            val sdfInput = if (entryStr.contains("T")) {
                SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.US)
            } else {
                SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US)
            }
            val date = sdfInput.parse(entryStr)
            if (date != null) SimpleDateFormat("dd/MM/yyyy", Locale("pt", "BR")).format(date) else today
        } catch (e: Exception) {
            try {
                val date = SimpleDateFormat("yyyy-MM-dd", Locale.US).parse(entryStr)
                if (date != null) SimpleDateFormat("dd/MM/yyyy", Locale("pt", "BR")).format(date) else today
            } catch (e2: Exception) {
                today
            }
        }
    } ?: today

    val initialPrevisao = osToEdit?.data_pronto?.let { dateStr ->
        try {
            val date = SimpleDateFormat("yyyy-MM-dd", Locale.US).parse(dateStr)
            if (date != null) {
                val calendar = java.util.Calendar.getInstance()
                calendar.time = date
                val dayOfWeekNum = calendar.get(java.util.Calendar.DAY_OF_WEEK)
                when (dayOfWeekNum) {
                    java.util.Calendar.SUNDAY -> "Domingo"
                    java.util.Calendar.MONDAY -> "Segunda-feira"
                    java.util.Calendar.TUESDAY -> "Terça-feira"
                    java.util.Calendar.WEDNESDAY -> "Quarta-feira"
                    java.util.Calendar.THURSDAY -> "Quinta-feira"
                    java.util.Calendar.FRIDAY -> "Sexta-feira"
                    java.util.Calendar.SATURDAY -> "Sábado"
                    else -> "Segunda-feira"
                }
            } else {
                "Segunda-feira"
            }
        } catch (e: Exception) {
            "Segunda-feira"
        }
    } ?: "Segunda-feira"

    var previsaoEntrega by remember { mutableStateOf(initialPrevisao) }
    var expandedPrevisao by remember { mutableStateOf(false) }
    val daysOfWeek = listOf(
        "Segunda-feira",
        "Terça-feira",
        "Quarta-feira",
        "Quinta-feira",
        "Sexta-feira",
        "Sábado",
        "Domingo",
        "Próxima semana"
    )

    var valor by remember { mutableStateOf(if (osToEdit != null) String.format(Locale.US, "%.2f", osToEdit.valor_orcamento).replace('.', ',') else "") }
    var statusPagamento by remember { mutableStateOf(osToEdit?.status_pagamento ?: "pendente") }
    var valorPago by remember { mutableStateOf(if (osToEdit != null && osToEdit.status_pagamento == "parcial") String.format(Locale.US, "%.2f", osToEdit.valor_pago).replace('.', ',') else "") }

    Scaffold(
        topBar = {
            TopAppBar(title = { Text(if (osToEdit != null) "Editar Ordem de Serviço" else "Nova Ordem de Serviço") })
        }
    ) { padding ->
        Column(
            modifier = Modifier
                .padding(padding)
                .padding(16.dp)
                .fillMaxSize()
                .verticalScroll(rememberScrollState()),
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            // 1. Modelo
            OutlinedTextField(value = modelo, onValueChange = { modelo = it }, label = { Text("Modelo") }, modifier = Modifier.fillMaxWidth())

            // 2. Descrição do Reparo
            OutlinedTextField(
                value = descricaoReparo,
                onValueChange = { descricaoReparo = it },
                label = { Text("Descrição do Reparo") },
                modifier = Modifier.fillMaxWidth(),
                maxLines = 3
            )

            // 3. Pronto Para (Previsão de Entrega)
            ExposedDropdownMenuBox(
                expanded = expandedPrevisao,
                onExpandedChange = { expandedPrevisao = !expandedPrevisao }
            ) {
                OutlinedTextField(
                    value = previsaoEntrega,
                    onValueChange = {},
                    readOnly = true,
                    label = { Text("Pronto para (Previsão de Entrega)") },
                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expandedPrevisao) },
                    modifier = Modifier.fillMaxWidth().menuAnchor()
                )
                ExposedDropdownMenu(
                    expanded = expandedPrevisao,
                    onDismissRequest = { expandedPrevisao = false }
                ) {
                    daysOfWeek.forEach { selectionOption ->
                        DropdownMenuItem(
                            text = { Text(selectionOption) },
                            onClick = {
                                previsaoEntrega = selectionOption
                                expandedPrevisao = false
                            }
                        )
                    }
                }
            }

            // 4. Data de Entrada (Read-only)
            OutlinedTextField(
                value = entryDate,
                onValueChange = {},
                readOnly = true,
                enabled = false,
                label = { Text("Data de Entrada") },
                modifier = Modifier.fillMaxWidth()
            )

            // 5. Nome do Cliente
            OutlinedTextField(value = nome, onValueChange = { nome = it }, label = { Text("Nome do Cliente") }, modifier = Modifier.fillMaxWidth())

            // 6. Telefone
            OutlinedTextField(
                value = telefone,
                onValueChange = { incoming ->
                    val digits = incoming.text.filter { it.isDigit() }.take(11)
                    val formatted = formatPhone(digits)
                    telefone = TextFieldValue(
                        text = formatted,
                        selection = androidx.compose.ui.text.TextRange(formatted.length)
                    )
                },
                label = { Text("Telefone") },
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone),
                modifier = Modifier.fillMaxWidth()
            )

            // 7. Valor
            OutlinedTextField(value = valor, onValueChange = { valor = it }, label = { Text("Valor Total (R$)") }, keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number), modifier = Modifier.fillMaxWidth())
            
            Text("Situação do Pagamento")
            Row {
                RadioButton(selected = statusPagamento == "pendente", onClick = { statusPagamento = "pendente" })
                Text("Pendente", modifier = Modifier.padding(top=14.dp, end=8.dp))
                RadioButton(selected = statusPagamento == "parcial", onClick = { statusPagamento = "parcial" })
                Text("Parcial", modifier = Modifier.padding(top=14.dp, end=8.dp))
                RadioButton(selected = statusPagamento == "total", onClick = { statusPagamento = "total" })
                Text("Total", modifier = Modifier.padding(top=14.dp))
            }

            if (statusPagamento == "parcial") {
                OutlinedTextField(value = valorPago, onValueChange = { valorPago = it }, label = { Text("Valor Pago (Adiantado)") }, keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number), modifier = Modifier.fillMaxWidth())
            }

            Row(
                modifier = Modifier.fillMaxWidth().padding(top = 16.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                OutlinedButton(
                    onClick = onBack,
                    modifier = Modifier.weight(1f)
                ) {
                    Text("Cancelar")
                }
                Button(
                    onClick = {
                        val parsedDate = calculateDateFromDayOfWeek(previsaoEntrega)
                        val doubleValor = valor.replace(',', '.').toDoubleOrNull() ?: 0.0
                        val doubleValorPago = if (statusPagamento == "total") doubleValor else (valorPago.replace(',', '.').toDoubleOrNull() ?: 0.0)

                        val request = OsRequest(
                            descricao_item = modelo.ifEmpty { "Equipamento" },
                            modelo = modelo,
                            defeito_relatado = descricaoReparo,
                            data_pronto = parsedDate,
                            nome = nome,
                            telefone = telefone.text,
                            valor = doubleValor,
                            status_pagamento = statusPagamento,
                            valor_pago = doubleValorPago
                        )
                        if (osToEdit != null) {
                            viewModel.updateOs(osToEdit.id, request, onSuccess = onBack)
                        } else {
                            viewModel.saveOs(request, onSuccess = onBack)
                        }
                    },
                    modifier = Modifier.weight(1f)
                ) {
                    Text("Salvar Serviço")
                }
            }
        }
    }
}
