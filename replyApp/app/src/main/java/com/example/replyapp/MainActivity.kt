package com.example.replyapp

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.*
import com.example.replyapp.api.OrdemServico
import com.example.replyapp.ui.CreateOsScreen
import com.example.replyapp.ui.HomeScreen
import com.example.replyapp.ui.SearchOsScreen
import com.example.replyapp.ui.theme.ReplyAppTheme
import androidx.compose.ui.Modifier

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            ReplyAppTheme {
                Surface(modifier = Modifier.fillMaxSize(), color = MaterialTheme.colorScheme.background) {
                    var currentScreen by remember { mutableStateOf("home") }
                    var selectedOsForEdit by remember { mutableStateOf<OrdemServico?>(null) }

                    when (currentScreen) {
                        "home" -> HomeScreen(
                            onNavigateToCreate = {
                                selectedOsForEdit = null
                                currentScreen = "create"
                            },
                            onNavigateToEdit = { os ->
                                selectedOsForEdit = os
                                currentScreen = "create"
                            },
                            onNavigateToSearch = {
                                currentScreen = "search"
                            }
                        )
                        "create" -> CreateOsScreen(
                            osToEdit = selectedOsForEdit,
                            onBack = { currentScreen = "home" }
                        )
                        "search" -> SearchOsScreen(
                            onBack = { currentScreen = "home" },
                            onNavigateToEdit = { os ->
                                selectedOsForEdit = os
                                currentScreen = "create"
                            }
                        )
                    }
                }
            }
        }
    }
}