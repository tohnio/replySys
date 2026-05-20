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

fun generatePrevisaoOptions(initialDate: String? = null): List<Pair<String, String>> {
    val options = mutableListOf<Pair<String, String>>()
    val current = java.util.Calendar.getInstance()
    
    val limit = java.util.Calendar.getInstance()
    while (limit.get(java.util.Calendar.DAY_OF_WEEK) != java.util.Calendar.MONDAY) {
        limit.add(java.util.Calendar.DAY_OF_YEAR, -1)
    }
    limit.add(java.util.Calendar.DAY_OF_YEAR, 11) // Friday of next week (Monday + 11 days)
    
    val sdfDate = java.text.SimpleDateFormat("yyyy-MM-dd", java.util.Locale.US)
    val todayDateStr = sdfDate.format(current.time)
    
    val seenCounts = mutableMapOf<Int, Int>()
    var iterations = 0
    
    while (!current.after(limit) && iterations < 30) {
        iterations++
        val dayOfWeek = current.get(java.util.Calendar.DAY_OF_WEEK)
        
        if (dayOfWeek != java.util.Calendar.SATURDAY && dayOfWeek != java.util.Calendar.SUNDAY) {
            val dateStr = sdfDate.format(current.time)
            val isToday = dateStr == todayDateStr
            
            val currentCount = (seenCounts[dayOfWeek] ?: 0) + 1
            seenCounts[dayOfWeek] = currentCount
            
            val label = if (isToday) {
                "Hoje"
            } else {
                val dayName = when (dayOfWeek) {
                    java.util.Calendar.MONDAY -> "Segunda-feira"
                    java.util.Calendar.TUESDAY -> "Terça-feira"
                    java.util.Calendar.WEDNESDAY -> "Quarta-feira"
                    java.util.Calendar.THURSDAY -> "Quinta-feira"
                    java.util.Calendar.FRIDAY -> "Sexta-feira"
                    else -> ""
                }
                
                if (currentCount > 1) {
                    "$dayName (Semana seguinte)"
                } else {
                    dayName
                }
            }
            options.add(Pair(label, dateStr))
        }
        current.add(java.util.Calendar.DAY_OF_YEAR, 1)
    }
    
    if (initialDate != null && options.none { it.second == initialDate }) {
        try {
            val date = java.text.SimpleDateFormat("yyyy-MM-dd", java.util.Locale.US).parse(initialDate)
            if (date != null) {
                val formattedLabel = java.text.SimpleDateFormat("dd/MM/yyyy", java.util.Locale("pt", "BR")).format(date)
                options.add(0, Pair(formattedLabel, initialDate))
            }
        } catch (e: Exception) {
            options.add(0, Pair(initialDate, initialDate))
        }
    }
    
    return options
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

    val previsaoOptions = remember(osToEdit) { generatePrevisaoOptions(osToEdit?.data_pronto) }
    val initialPrevisaoLabel = remember(osToEdit, previsaoOptions) {
        val dateStr = osToEdit?.data_pronto
        if (dateStr != null) {
            val matchingOption = previsaoOptions.find { it.second == dateStr }
            if (matchingOption != null) {
                matchingOption.first
            } else {
                try {
                    val date = SimpleDateFormat("yyyy-MM-dd", Locale.US).parse(dateStr)
                    if (date != null) {
                        SimpleDateFormat("dd/MM/yyyy", Locale("pt", "BR")).format(date)
                    } else {
                        dateStr
                    }
                } catch (e: Exception) {
                    dateStr
                }
            }
        } else {
            previsaoOptions.firstOrNull()?.first ?: "Segunda-feira"
        }
    }

    var previsaoEntrega by remember { mutableStateOf(initialPrevisaoLabel) }
    var expandedPrevisao by remember { mutableStateOf(false) }

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
                    previsaoOptions.forEach { option ->
                        DropdownMenuItem(
                            text = { Text(option.first) },
                            onClick = {
                                previsaoEntrega = option.first
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
                        val selectedOption = previsaoOptions.find { it.first == previsaoEntrega }
                        val parsedDate = if (selectedOption != null) {
                            selectedOption.second
                        } else {
                            try {
                                val date = SimpleDateFormat("dd/MM/yyyy", Locale("pt", "BR")).parse(previsaoEntrega)
                                if (date != null) SimpleDateFormat("yyyy-MM-dd", Locale.US).format(date) else previsaoEntrega
                            } catch (e: Exception) {
                                previsaoEntrega
                            }
                        }
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
